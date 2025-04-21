import json
import time
import requests
from http.server import BaseHTTPRequestHandler, HTTPServer
from hashlib import sha256
from ecdsa import SigningKey, VerifyingKey, SECP256k1

PREDEFINED_DATA = {
    "needer": {"public_key": "needer_public_key_123", "private_key": "needer_private_key_hex_123", "signature": "abcdef123456"},
    "operator": {"public_key": "operator_public_key_456", "private_key": "operator_private_key_hex_456"},
    "donor": {"id": "donor1", "public_key": "donor_public_key_789", "private_key": "donor_private_key_hex_789"},
    "order": {"order_id": "12345", "foodbank_id": "foodbank1", "warehouse_id": "warehouse1", "point_id": "point1",
              "items": [{"code": "prod1", "cost": 200, "donor_id": "donor1", "blockchain_address": "donor_public_key_789"}]},
    "invoice": {"id": "invoice123", "hash": "invoice_hash_example"}
}

class Blockchain:
    def __init__(self):
        self.chain = [{'index': 0, 'timestamp': time.time(), 'transactions': [], 'previous_hash': '0'}]
        self.mempool = []
        self.port = 8002
        self.authorized_nodes = ['http://localhost:8000', 'http://localhost:8001']
        self.private_key = SigningKey.from_string(bytes.fromhex("18e14a7b6a307f426a94f8114701e7c8e774e7f9a47e2c2035db29a206321725"), curve=SECP256k1)
        self.public_key = self.private_key.verifying_key.to_string().hex()
        self.node_id = '2'

    def validate_chain(self, chain):
        for i in range(1, len(chain)):
            block = chain[i]
            prev_block = chain[i-1]
            if block['previous_hash'] != sha256(json.dumps(prev_block, sort_keys=True).encode()).hexdigest():
                return False
            creator_key = VerifyingKey.from_string(bytes.fromhex(block['creator_public_key']), curve=SECP256k1)
            block_data = json.dumps({k: v for k, v in block.items() if k != 'signature'}, sort_keys=True).encode()
            if not creator_key.verify(bytes.fromhex(block['signature']), block_data):
                return False
        return True

    def resolve(self):
        longest_chain = self.chain
        for node in self.authorized_nodes:
            try:
                response = requests.get(f'{node}/chain', timeout=5)
                if response.status_code == 200:
                    new_chain = response.json()
                    if len(new_chain) > len(longest_chain) and self.validate_chain(new_chain):
                        longest_chain = new_chain
                        print(f"Validator (ID: {self.node_id}) resolved chain from {node}, new length: {len(longest_chain)}")
            except Exception as e:
                print(f"Validator (ID: {self.node_id}) failed to resolve chain from {node}: {e}")
        if longest_chain != self.chain:
            self.chain = longest_chain
            return True
        return False

    def fetch_mempool(self):
        try:
            response = requests.get('http://localhost:8001/mempool', timeout=5)
            if response.status_code == 200:
                self.mempool = response.json()
                print(f"Validator (ID: {self.node_id}) fetched mempool from operator")
                return True
            else:
                print(f"Validator (ID: {self.node_id}) failed to fetch mempool: {response.status_code}")
                return False
        except Exception as e:
            print(f"Validator (ID: {self.node_id}) failed to fetch mempool: {e}")
            return False

    def create_block(self):
        if not self.fetch_mempool():
            print(f"Validator (ID: {self.node_id}) no transactions in mempool, skipping block creation")
            return False
        previous_block = self.chain[-1]
        new_block = {
            'index': len(self.chain),
            'timestamp': time.time(),
            'transactions': self.mempool[:],
            'previous_hash': sha256(json.dumps(previous_block, sort_keys=True).encode()).hexdigest(),
            'creator_public_key': self.public_key
        }
        new_block['signature'] = self.private_key.sign(json.dumps(new_block, sort_keys=True).encode()).hex()
        print(f"Validator (ID: {self.node_id}) proposing block #{new_block['index']}")
        # Эмуляция голосования
        votes = self._vote_on_block(new_block)
        if votes >= 2:  # Требуется 2/3 голоса (из 3 узлов)
            self.chain.append(new_block)
            self.mempool = []
            print(f"Validator (ID: {self.node_id}) created block #{new_block['index']}")
            return True
        else:
            print(f"Validator (ID: {self.node_id}) block #{new_block['index']} rejected, insufficient votes")
            return False

    def _vote_on_block(self, block):
        votes = 0
        for node in self.authorized_nodes + [f'http://localhost:{self.port}']:
            try:
                response = requests.post(f'{node}/vote', json={'block': block}, timeout=5)
                if response.status_code == 200 and response.json().get('vote') == 'YES':
                    votes += 1
                    print(f"Node {node} voted YES for block #{block['index']}")
            except Exception as e:
                print(f"Failed to get vote from {node}: {e}")
        return votes

    def calculate_balances(self):
        balances = {}
        for block in self.chain:
            for tx in block['transactions']:
                if "type" in tx and tx["type"] == "mint":
                    balances[tx["beneficiary"]] = balances.get(tx["beneficiary"], 0) + tx["amount"]
                elif tx.get('status') == 'completed':
                    total_cost = sum(item['cost'] for item in tx['order'])
                    balances[tx['beneficiary']] = balances.get(tx['beneficiary'], 0) - total_cost
                    for item in tx['order']:
                        donor_address = item['blockchain_address']
                        balances[donor_address] = balances.get(donor_address, 0) + item['cost']
        return balances

    def get_balance(self, address):
        return self.calculate_balances().get(address, 0)

class BlockchainHandler(BaseHTTPRequestHandler):
    def do_GET(self):
        if self.path == '/chain':
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps(blockchain.chain).encode())
        elif self.path == '/mempool':
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps(blockchain.mempool).encode())
        elif self.path.startswith('/balance/'):
            address = self.path.split('/')[2]
            balance = blockchain.get_balance(address)
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps({'address': address, 'balance': balance}).encode())
        elif self.path == '/wallets':
            balances = blockchain.calculate_balances()
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps(balances).encode())
        else:
            self.send_response(404)
            self.end_headers()

    def do_POST(self):
        try:
            content_length = int(self.headers['Content-Length'])
            post_data = json.loads(self.rfile.read(content_length)) if content_length > 0 else {}
        except Exception as e:
            self.send_response(400)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps({'status': 'error', 'message': f'Invalid request: {str(e)}'}).encode())
            return

        if self.path == '/create_block':
            if blockchain.create_block():
                self.send_response(200)
                self.send_header('Content-type', 'application/json')
                self.end_headers()
                self.wfile.write(json.dumps({'status': 'ok', 'message': 'Block created'}).encode())
            else:
                self.send_response(400)
                self.send_header('Content-type', 'application/json')
                self.end_headers()
                self.wfile.write(json.dumps({'status': 'error', 'message': 'Failed to create block'}).encode())
        elif self.path == '/vote':
            block = post_data.get('block')
            if block and blockchain.validate_chain([block]):
                self.send_response(200)
                self.send_header('Content-type', 'application/json')
                self.end_headers()
                self.wfile.write(json.dumps({'vote': 'YES'}).encode())
            else:
                self.send_response(400)
                self.send_header('Content-type', 'application/json')
                self.end_headers()
                self.wfile.write(json.dumps({'vote': 'NO', 'message': 'Invalid block'}).encode())
        elif self.path == '/resolve':
            success = blockchain.resolve()
            self.send_response(200 if success else 400)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps({'status': 'ok' if success else 'error', 'message': 'Chain resolved' if success else 'No changes'}).encode())
        else:
            self.send_response(403)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps({'status': 'error', 'message': 'Forbidden'}).encode())

blockchain = Blockchain()
server = HTTPServer(('localhost', 8002), BlockchainHandler)
print("Starting validator node on port 8002 with ID 2")
server.serve_forever()