# backend/routers/warehouse.py

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from db import get_db
from models import models
from schemas import schemas

router = APIRouter(
    prefix="/api/warehouse",
    tags=["Склад"]
)

@router.get("/tasks")
def get_tasks(db: Session = Depends(get_db)):
    """
    Получить список заданий на отгрузку по пунктам выдачи.
    """
    tasks = (
        db.query(models.Order)
        .filter(models.Order.status == "pending")
        .all()
    )

    result = {}
    for task in tasks:
        if task.pickup_point_id not in result:
            result[task.pickup_point_id] = []
        result[task.pickup_point_id].append({
            "order_id": task.id,
            "items": task.items,
        })

    return {"tasks": result}


@router.get("/task/{pickup_point_id}")
def get_task_details(pickup_point_id: int, db: Session = Depends(get_db)):
    """
    Получить детали конкретного задания на отгрузку
    """
    task_orders = (
        db.query(models.Order)
        .filter(models.Order.pickup_point_id == pickup_point_id)
        .filter(models.Order.status == "pending")
        .all()
    )

    if not task_orders:
        raise HTTPException(status_code=404, detail="Задание не найдено")

    items_summary = {}

    for order in task_orders:
        for item in order.items:
            if item.product_type not in items_summary:
                items_summary[item.product_type] = 0
            items_summary[item.product_type] += item.quantity

    return {
        "pickup_point": pickup_point_id,
        "order_count": len(task_orders),
        "items": [{"name": k, "quantity": v} for k, v in items_summary.items()]
    }


@router.post("/confirm")
def confirm_shipment(task_id: int, qr_code: str, db: Session = Depends(get_db)):
    """
    Подтвердить отгрузку заказа со склада (по заданию и QR-коду)
    """
    task_orders = (
        db.query(models.Order)
        .filter(models.Order.pickup_point_id == task_id)
        .filter(models.Order.status == "pending")
        .all()
    )

    if not task_orders:
        raise HTTPException(status_code=404, detail="Задания не найдены")

    for order in task_orders:
        order.status = "in_transit"
        order.qr_code = qr_code

    db.commit()

    return {"message": "Отгрузка подтверждена"}
