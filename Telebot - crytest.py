import logging
import sqlite3
import json
from web3 import Web3
from datetime import datetime
from aiogram import Bot, Dispatcher, types
from aiogram.utils import executor
from aiogram.contrib.middlewares.logging import LoggingMiddleware

# Замените на ваш токен бота
TOKEN = '7766815546:AAHilv5biwBWjz4VA7vW1WTZ1xCk660Nwcg'

# Замените на ваш URL Ganache
GANACHE_URL = 'http://127.0.0.1:7545'

# Замените на адрес вашего смарт-контракта
CONTRACT_ADDRESS = 'YOUR_CONTRACT_ADDRESS'

# Замените на ABI вашего смарт-контракта
CONTRACT_ABI = json.loads('''[
    {"inputs":[{"internalType":"address[3]","name":"_owners","type":"address[3]"}],
     "stateMutability":"nonpayable","type":"constructor"},
    {"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"sender","type":"address"},
     {"indexed":false,"internalType":"uint256","name":"amount","type":"uint256"},
     {"indexed":false,"internalType":"uint256","name":"balance","type":"uint256"}],
     "name":"Deposit","type":"event"},
    {"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"owner","type":"address"},
     {"indexed":false,"internalType":"uint256","name":"amount","type":"uint256"}],
     "name":"SubmitTransaction","type":"event"},
    {"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"owner","type":"address"}],
     "name":"ConfirmTransaction","type":"event"},
    {"anonymous":false,"inputs":[{"indexed":true,"internalType":"address","name":"owner","type":"address"},
     {"indexed":false,"internalType":"uint256","name":"amount","type":"uint256"}],
     "name":"ExecuteTransaction","type":"event"},
    {"inputs":[],"name":"confirmationCount","outputs":[{"internalType":"uint256","name":"","type":"uint256"}],
     "stateMutability":"view","type":"function"},
    {"inputs":[],"name":"confirmTransaction","outputs":[],"stateMutability":"nonpayable","type":"function"},
    {"inputs":[{"internalType":"address","name":"_to","type":"address"}],
     "name":"executeTransaction","outputs":[],"stateMutability":"nonpayable","type":"function"}
]''')

# Инициализация бота и диспетчера
bot = Bot(token=TOKEN)
dp = Dispatcher(bot)

# Подключение к Ganache
w3 = Web3(Web3.HTTPProvider(GANACHE_URL))
contract = w3.eth.contract(address=CONTRACT_ADDRESS, abi=CONTRACT_ABI)

# Словарь для хранения состояния подтверждений
confirmations = {}

# Настройка логгера
logger = logging.getLogger('db_logger')
logger.setLevel(logging.INFO)

# Создание обработчика для записи в базу данных
class SQLiteHandler(logging.Handler):
    def __init__(self, db_path):
        super().__init__()
        self.db_path = db_path

    def emit(self, record):
        log_entry = self.format(record)
        timestamp = datetime.now().isoformat()
        conn = sqlite3.connect(self.db_path)
        cursor = conn.cursor()
        cursor.execute('INSERT INTO logs (timestamp, message) VALUES (?, ?)', (timestamp, log_entry))
        conn.commit()
        conn.close()

# Добавление обработчика к логгеру
sqlite_handler = SQLiteHandler('users.db')
logger.addHandler(sqlite_handler)

# Обработчик команды /start
@dp.message_handler(commands=['start'])
async def send_welcome(message: types.Message):
    await message.reply("Привет! Я бот для подтверждения транзакций в блокчейне Ganache.")
    logger.info(f"User {message.from_user.id} started the bot.")

# Обработчик команды /confirm
@dp.message_handler(commands=['confirm'])
async def confirm_transaction(message: types.Message):
    user_id = message.from_user.id
    conn = sqlite3.connect('users.db')
    cursor = conn.cursor()
    cursor.execute('SELECT wallet_address, private_key FROM users WHERE telegram_id = ?', (user_id,))
    user_data = cursor.fetchone()
    conn.close()

    if user_data:
        wallet_address, private_key = user_data
        if user_id not in confirmations:
            confirmations[user_id] = True
            await message.reply("Транзакция подтверждена.")
            logger.info(f"User {user_id} confirmed the transaction.")

            # Подпись транзакции
            nonce = w3.eth.get_transaction_count(wallet_address)
            txn = contract.functions.confirmTransaction().build_transaction({
                'chainId': 1337,
                'gas': 1000000,
                'gasPrice': w3.to_wei('1', 'gwei'),
                'nonce': nonce,
            })
            signed_txn = w3.eth.account.sign_transaction(txn, private_key=private_key)
            txn_hash = w3.eth.send_raw_transaction(signed_txn.rawTransaction)
            await message.reply(f"Транзакция отправлена в блокчейн. Хэш: {txn_hash.hex()}")
            logger.info(f"Transaction sent to blockchain. Hash: {txn_hash.hex()}")

            # Проверка, если все три пользователя подтвердили
            if len(confirmations) == 3:
                await message.reply("Все пользователи подтвердили транзакцию.")
                logger.info("All users confirmed the transaction.")
                # Выполнение транзакции
                nonce = w3.eth.get_transaction_count(wallet_address)
                txn = contract.functions.executeTransaction(w3.to_checksum_address('0x789...')).build_transaction({
                    'chainId': 1337,
                    'gas': 1000000,
                    'gasPrice': w3.to_wei('1', 'gwei'),
                    'nonce': nonce,
                })
                signed_txn = w3.eth.account.sign_transaction(txn, private_key=private_key)
                txn_hash = w3.eth.send_raw_transaction(signed_txn.rawTransaction)
                await message.reply(f"Транзакция выполнена. Хэш: {txn_hash.hex()}")
                logger.info(f"Transaction executed. Hash: {txn_hash.hex()}")
                await message.reply("1 ETH списан у пользователя 1. 1 ETH начислен пользователю 3.")
                logger.info("1 ETH deducted from user 1. 1 ETH credited to user 3.")
                confirmations.clear()
        else:
            await message.reply("Вы уже подтвердили транзакцию.")
            logger.info(f"User {user_id} tried to confirm the transaction again.")
    else:
        await message.reply("Вы не авторизованы.")
        logger.info(f"Unauthorized user {user_id} tried to confirm the transaction.")

# Запуск бота
if __name__ == '__main__':
    executor.start_polling(dp, skip_updates=True)