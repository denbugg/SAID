import sqlite3

# Создание базы данных
conn = sqlite3.connect('users.db')
cursor = conn.cursor()

# Создание таблицы логов
cursor.execute('''
CREATE TABLE IF NOT EXISTS logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    timestamp TEXT,
    message TEXT
)
''')

conn.commit()
conn.close()