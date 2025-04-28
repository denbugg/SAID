import http.server
import socketserver
import os

# Порт для сервера (можешь поменять если хочешь)
PORT = 8001

# Путь до папки frontend (указываем папку где лежит скрипт)
os.chdir(os.path.dirname(os.path.abspath(__file__)))

Handler = http.server.SimpleHTTPRequestHandler

with socketserver.TCPServer(("", PORT), Handler) as httpd:
    print(f"Сервер фронтенда запущен на http://localhost:{PORT}")
    print("Нажмите CTRL+C для остановки сервера.")
    httpd.serve_forever()
