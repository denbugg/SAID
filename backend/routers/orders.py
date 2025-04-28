# backend/routers/orders.py

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from schemas import schemas
from models import models
from db import SessionLocal
import datetime

router = APIRouter(
    prefix="/api/orders",
)

def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

@router.post("/create")
def create_order(order_data: schemas.OrderCreate, db: Session = Depends(get_db)):
    new_order = models.Order(
        user_id=order_data.user_id,
        status="new",
        created_at=datetime.datetime.utcnow()
    )
    db.add(new_order)
    db.commit()
    db.refresh(new_order)

    for item in order_data.items:
        order_item = models.OrderItem(
            order_id=new_order.id,
            product_type_id=item.product_type_id,
            quantity_requested=item.quantity_requested,
            quantity_scanned=0
        )
        db.add(order_item)

    db.commit()
    return {"order_id": new_order.id}
