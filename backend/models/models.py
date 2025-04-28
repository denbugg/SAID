# backend/models/models.py

from sqlalchemy import Column, Integer, String, Date, ForeignKey, DateTime, Float, Enum, Boolean
from sqlalchemy.orm import declarative_base, relationship
import enum
import datetime
from db import Base


class Demand(Base):
    __tablename__ = "demands"

    id = Column(Integer, primary_key=True, index=True)
    date = Column(Date, unique=True, nullable=False)

    # Гуманитарная помощь
    flour = Column(Integer, default=0)
    meat = Column(Integer, default=0)
    fish = Column(Integer, default=0)
    sweet = Column(Integer, default=0)
    vegetable = Column(Integer, default=0)
    groats = Column(Integer, default=0)
    sugar = Column(Integer, default=0)
    pasta = Column(Integer, default=0)
    candy = Column(Integer, default=0)
    fastfood = Column(Integer, default=0)
    tea = Column(Integer, default=0)
    oil = Column(Integer, default=0)

    # Гигиеническая помощь
    soap = Column(Integer, default=0)
    shampoo = Column(Integer, default=0)
    wipes = Column(Integer, default=0)
    toothpaste = Column(Integer, default=0)
    toothbrush = Column(Integer, default=0)
    toilet_paper = Column(Integer, default=0)
    women_hygiene = Column(Integer, default=0)
    razor = Column(Integer, default=0)
Base = declarative_base()

class UserRole(str, enum.Enum):
    NEEDER = "needer"
    DONOR = "donor"
    WAREHOUSE_OPERATOR = "warehouse_operator"
    DELIVERY_OPERATOR = "delivery_operator"

class User(Base):
    __tablename__ = 'users'

    id = Column(Integer, primary_key=True)
    name = Column(String, nullable=False)
    phone = Column(String, unique=True, nullable=False)
    role = Column(Enum(UserRole), nullable=False)
    hashed_password = Column(String, nullable=False)
    public_key = Column(String)
    balance_tokens = Column(Integer, default=0)

class CategoryType(str, enum.Enum):
    HUMANITARIAN = "humanitarian"
    HYGIENE = "hygiene"

class Category(Base):
    __tablename__ = 'categories'

    id = Column(Integer, primary_key=True)
    name = Column(String, nullable=False)
    type = Column(Enum(CategoryType), nullable=False)

class ProductType(Base):
    __tablename__ = 'product_types'

    id = Column(Integer, primary_key=True)
    name = Column(String, nullable=False)
    category_id = Column(Integer, ForeignKey('categories.id'))
    price_tokens = Column(Integer, nullable=False)
    category = relationship("Category")

class ProductStatus(str, enum.Enum):
    ON_WAREHOUSE = "on_warehouse"
    ASSIGNED_TO_TASK = "assigned_to_task"
    DELIVERED_TO_POINT = "delivered_to_point"
    ASSIGNED_TO_ORDER = "assigned_to_order"
    ISSUED = "issued"

class Product(Base):
    __tablename__ = 'products'

    uid = Column(String, primary_key=True)
    product_type_id = Column(Integer, ForeignKey('product_types.id'))
    status = Column(Enum(ProductStatus), default=ProductStatus.ON_WAREHOUSE)
    warehouse_id = Column(Integer)
    donor_id = Column(Integer, ForeignKey('users.id'))
    delivery_point_id = Column(Integer)
    order_id = Column(Integer)
    task_id = Column(Integer)
    invoice_id = Column(Integer)

    product_type = relationship("ProductType")
    donor = relationship("User", foreign_keys=[donor_id])

class Demand(Base):
    __tablename__ = 'demands'

    id = Column(Integer, primary_key=True)
    date_start = Column(DateTime, default=datetime.datetime.utcnow)
    humanitarian_quantity = Column(Integer, default=0)
    hygiene_quantity = Column(Integer, default=0)
    is_active = Column(Boolean, default=True)

class Order(Base):
    __tablename__ = 'orders'

    id = Column(Integer, primary_key=True)
    user_id = Column(Integer, ForeignKey('users.id'))
    status = Column(String, default="new")
    created_at = Column(DateTime, default=datetime.datetime.utcnow)

class OrderItem(Base):
    __tablename__ = 'order_items'

    id = Column(Integer, primary_key=True)
    order_id = Column(Integer, ForeignKey('orders.id'))
    product_type_id = Column(Integer, ForeignKey('product_types.id'))
    quantity_requested = Column(Integer)
    quantity_scanned = Column(Integer, default=0)

class Task(Base):
    __tablename__ = 'tasks'

    id = Column(Integer, primary_key=True)
    warehouse_id = Column(Integer)
    delivery_point_id = Column(Integer)
    status = Column(String, default="new")
    created_at = Column(DateTime, default=datetime.datetime.utcnow)

class Delivery(Base):
    __tablename__ = 'deliveries'

    id = Column(Integer, primary_key=True)
    related_id = Column(Integer)
    delivery_type = Column(String)
    confirmation_code = Column(String)
    signature = Column(String)
    created_at = Column(DateTime, default=datetime.datetime.utcnow)

class Invoice(Base):
    __tablename__ = 'invoices'

    id = Column(Integer, primary_key=True)
    donor_id = Column(Integer, ForeignKey('users.id'))
    created_at = Column(DateTime, default=datetime.datetime.utcnow)
    file_path = Column(String)

class BlockchainTransaction(Base):
    __tablename__ = 'blockchain_transactions'

    id = Column(Integer, primary_key=True)
    action = Column(String)
    related_id = Column(Integer)
    signed_data = Column(String)
    block_hash = Column(String)
    created_at = Column(DateTime, default=datetime.datetime.utcnow)
