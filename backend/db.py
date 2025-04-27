# db.py

from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker

# Создание базы данных SQLite
DATABASE_URL = "sqlite:///./said_done.db"

engine = create_engine(
    DATABASE_URL, connect_args={"check_same_thread": False}
)

SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
