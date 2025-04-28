# backend/populate_demands.py

from sqlalchemy.orm import Session
from datetime import datetime, timedelta
import models
from db import SessionLocal

categories = [
    'flour', 'meat', 'fish', 'sweet', 'vegetable', 'groats', 'sugar', 'pasta', 'candy', 'fastfood', 'tea', 'oil',
    'soap', 'shampoo', 'wipes', 'toothpaste', 'toothbrush', 'toilet_paper', 'women_hygiene', 'razor'
]

def populate_demands():
    db: Session = SessionLocal()
    today = datetime.now().date()

    for i in range(30):
        demand_date = today + timedelta(days=i)

        demand = models.Demand(date=demand_date)

        # Установить по 1000 в каждую категорию
        for category in categories:
            setattr(demand, category, 1000)

        db.add(demand)

    db.commit()
    db.close()
    print("Таблица 'demands' успешно заполнена тестовыми данными.")

if __name__ == "__main__":
    populate_demands()
