# backend/schemas/schemas.py

from pydantic import BaseModel
from typing import Optional, List
import datetime

class UserBase(BaseModel):
    name: str
    phone: str
    role: str

class UserCreate(UserBase):
    password: str

class UserLogin(BaseModel):
    phone: str
    password: str

class UserOut(UserBase):
    id: int
    public_key: Optional[str]
    balance_tokens: Optional[int]

    class Config:
        orm_mode = True

class CategoryBase(BaseModel):
    name: str
    type: str

class CategoryOut(CategoryBase):
    id: int

    class Config:
        orm_mode = True

class ProductTypeBase(BaseModel):
    name: str
    category_id: int
    price_tokens: int

class ProductTypeOut(ProductTypeBase):
    id: int

    class Config:
        orm_mode = True

class ProductBase(BaseModel):
    uid: str
    product_type_id: int

class ProductOut(ProductBase):
    status: str
    warehouse_id: Optional[int]
    donor_id: Optional[int]
    delivery_point_id: Optional[int]
    order_id: Optional[int]
    task_id: Optional[int]

    class Config:
        orm_mode = True

class DemandBase(BaseModel):
    humanitarian_quantity: int
    hygiene_quantity: int

class DemandCreate(DemandBase):
    pass

class DemandOut(DemandBase):
    id: int
    date_start: datetime.datetime
    is_active: bool

    class Config:
        orm_mode = True

class OrderItemBase(BaseModel):
    product_type_id: int
    quantity_requested: int

class OrderItemOut(OrderItemBase):
    id: int
    quantity_scanned: int

    class Config:
        orm_mode = True

class OrderBase(BaseModel):
    user_id: int

class OrderCreate(OrderBase):
    items: List[OrderItemBase]

class OrderOut(OrderBase):
    id: int
    status: str
    created_at: datetime.datetime
    items: List[OrderItemOut]

    class Config:
        orm_mode = True

class TaskBase(BaseModel):
    warehouse_id: int
    delivery_point_id: int

class TaskOut(TaskBase):
    id: int
    status: str
    created_at: datetime.datetime

    class Config:
        orm_mode = True

class BlockchainTransactionBase(BaseModel):
    action: str
    related_id: int
    signed_data: str

class BlockchainTransactionOut(BlockchainTransactionBase):
    id: int
    block_hash: str
    created_at: datetime.datetime

    class Config:
        orm_mode = True
