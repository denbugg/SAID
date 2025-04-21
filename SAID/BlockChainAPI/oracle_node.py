import json
import time
import requests
from http.server import BaseHTTPRequestHandler, HTTPServer
from hashlib import sha256
from ecdsa import SigningKey, VerifyingKey, SECP256k1

PREDEFINED_DATA = {
    "needer": {
        "public_key": "04d8e9f2a3b4c5d67890abcdef1234567890fedcba0987654321abcdef1234567890a1b2c3d4e5f67890abcdef1234567890fedcba0987654321abcdef1234567890",
        "private_key": "b9c8d7e6f5a4b3c2d1e0f1234567890abcdef1234567890fedcba0987654321",
        "signature": "1a2b3c4d5e6f7890abcdef1234567890fedcba0987654321abcdef1234567890a1b2c3d4e5f67890abcdef1234567890fedcba0987654321abcdef1234567890fe02"
    },
    "operator": {
        "public_key": "04e1f2a3b4c5d67890abcdef1234567890fedcba0987654321abcdef1234567890b2c3d4e5f67890abcdef1234567890fedcba0987654321abcdef1234567890a1b2",
        "private_key": "c8d7e6f5a4b3c2d1e0f1234567890abcdef1234567890fedcba0987654321a2b3"
    },
    "donor": {
        "id": "donor_987654",
        "public_key": "04f3a4b5c6d7e890abcdef1234567890fedcba0987654321abcdef1234567890c3d4e5f67890abcdef1234567890fedcba0987654321abcdef1234567890b2c3d4e5",
        "private_key": "d7e6f5a4b3c2d1e0f1234567890abcdef1234567890fedcba0987654321b3c4d5"
    },
    "order": {
        "order_id": "ord_20250317_987654321",
        "foodbank_id": "fb_001_kursk",
        "warehouse_id": "wh_001_kursk",
        "point_id": "pt_001_kursk",
        "items": [
            {"code": "4601234567890", "name": "Консервы мясные 'Говядина тушёная'", "cost": 20, "donor_id": "donor_987654", "blockchain_address": "04f3a4b5c6d7e890abcdef1234567890fedcba0987654321abcdef1234567890c3d4e5f67890abcdef1234567890fedcba0987654321abcdef1234567890b2c3d4e5"},
            {"code": "4601234567906", "name": "Консервы рыбные 'Горбуша в масле'", "cost": 25, "donor_id": "donor_987654", "blockchain_address": "04f3a4b5c6d7e890abcdef1234567890fedcba0987654321abcdef1234567890c3d4e5f67890abcdef1234567890fedcba0987654321abcdef1234567890b2c3d4e5"},
            {"code": "4601234567913", "name": "Паштет из индейки 'Нежный'", "cost": 30, "donor_id": "donor_987654", "blockchain_address": "04f3a4b5c6d7e890abcdef1234567890fedcba0987654321abcdef1234567890c3d4e5f67890abcdef1234567890fedcba0987654321abcdef1234567890b2c3d4e5"},
            {"code": "4601234567920", "name": "Макароны Makfa 'Спираль'", "cost": 35, "donor_id": "donor_987654", "blockchain_address": "04f3a4b5c6d7e890abcdef1234567890fedcba0987654321abcdef1234567890c3d4e5f67890abcdef1234567890fedcba0987654321abcdef1234567890b2c3d4e5"},
            {"code": "4601234567937", "name": "Мука 'Пшеничная высший сорт'", "cost": 40, "donor_id": "donor_987654", "blockchain_address": "04f3a4b5c6d7e890abcdef1234567890fedcba0987654321abcdef1234567890c3d4e5f67890abcdef1234567890fedcba0987654321abcdef1234567890b2c3d4e5"}
        ]
    },
    "invoice": {
        "id": "inv_20250317_001",
        "hash": "e9f8d7c6b5a4f3e2d1c0b9a8f7e6d5c4b3a2f1e0d9c8b7a6f5e4d3c2b1a0f9e8d7c6b5"
    }
}


class Blockchain:
    def __init__(self):
        self.chain = [{'index': 0, 'timestamp': time.time(), 'transactions': [], 'previous_hash': '0'}]
        self.mempool = []
        self.port = 8000
        self.authorized_nodes = ['http://localhost:8001', 'http://localhost:8002']
        self.private_key = SigningKey.from_string(bytes.fromhex("18e14a7b6a307f426a94f8114701e7c8e774e7f9a47e2c2035db29a206321725"), curve=SECP256k1)
        self.public_key = self.private_key.verifying_key.to_string().hex()
        self.node_id = '0'

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
                        print(f"Oracle (ID: {self.node_id}) resolved chain from {node}, new length: {len(longest_chain)}")
            except Exception as e:
                print(f"Oracle (ID: {self.node_id}) failed to resolve chain from {node}: {e}")
        if longest_chain != self.chain:
            self.chain = longest_chain
            return True
        return False

    def mint_tokens(self, beneficiary, amount):
        transaction = {"type": "mint", "beneficiary": beneficiary, "amount": amount, "timestamp": time.time()}
        previous_block = self.chain[-1]
        new_block = {
            'index': len(self.chain),
            'timestamp': time.time(),
            'transactions': [transaction],
            'previous_hash': sha256(json.dumps(previous_block, sort_keys=True).encode()).hexdigest(),
            'creator_public_key': self.public_key  # Используем публичный ключ вместо node_id
        }
        new_block['signature'] = self.private_key.sign(json.dumps(new_block, sort_keys=True).encode()).hex()
        self.chain.append(new_block)
        print(f"Oracle (ID: {self.node_id}) minted {amount} tokens for {beneficiary} in block {new_block['index']}")
        return transaction

    def create_smart_contract(self, order_data=None):
        order_data = order_data or {
            "order_id": PREDEFINED_DATA["order"]["order_id"],
            "beneficiary": PREDEFINED_DATA["needer"]["public_key"],
            "foodbank_id": PREDEFINED_DATA["order"]["foodbank_id"],
            "warehouse_id": PREDEFINED_DATA["order"]["warehouse_id"],
            "point_id": PREDEFINED_DATA["order"]["point_id"],
            "order": PREDEFINED_DATA["order"]["items"],
            "beneficiary_signature": PREDEFINED_DATA["needer"]["signature"]
        }
        contract_id = sha256(f"{order_data['order_id']}{time.time()}".encode()).hexdigest()
        transaction = {
            'contract_id': contract_id,
            'order_id': order_data['order_id'],
            'beneficiary': order_data['beneficiary'],
            'foodbank_id': order_data['foodbank_id'],
            'warehouse_id': order_data['warehouse_id'],
            'point_id': order_data['point_id'],
            'order': order_data['order'],
            'status': 'pending',
            'beneficiary_signature': order_data['beneficiary_signature'],
            'invoice': {}
        }
        try:
            response = requests.post('http://localhost:8001/contracts/pending', json=transaction, timeout=5)
            if response.status_code == 200:
                print(f"Oracle (ID: {self.node_id}) created contract {contract_id} and sent to operator")
                return transaction
            else:
                print(f"Oracle (ID: {self.node_id}) failed to send contract {contract_id}: {response.status_code}")
                return None
        except Exception as e:
            print(f"Oracle (ID: {self.node_id}) failed to send contract {contract_id}: {e}")
            return None

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

        if self.path == '/mint_tokens':
            beneficiary = post_data.get('beneficiary', PREDEFINED_DATA["needer"]["public_key"])
            amount = post_data.get('amount', 200)
            transaction = blockchain.mint_tokens(beneficiary, amount)
            self.send_response(200)
            self.send_header('Content-type', 'application/json')
            self.end_headers()
            self.wfile.write(json.dumps({'status': 'ok', 'transaction': transaction}).encode())
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
        elif self.path == '/contracts/new':
            contract = blockchain.create_smart_contract(post_data)
            if contract:
                self.send_response(200)
                self.send_header('Content-type', 'application/json')
                self.end_headers()
                self.wfile.write(json.dumps({'status': 'ok', 'contract': contract}).encode())
            else:
                self.send_response(400)
                self.send_header('Content-type', 'application/json')
                self.end_headers()
                self.wfile.write(json.dumps({'status': 'error', 'message': 'Failed to create contract'}).encode())
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
server = HTTPServer(('localhost', 8000), BlockchainHandler)
print("Starting oracle node on port 8000 with ID 0")
server.serve_forever()