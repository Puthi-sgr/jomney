-- 1) CUSTOMER TABLE
CREATE TABLE customer (
  id          BIGSERIAL PRIMARY KEY,
  email       VARCHAR(255) UNIQUE NOT NULL,
  password    VARCHAR(255) NOT NULL,      -- hashed
  name        VARCHAR(100) NOT NULL,
  address     VARCHAR(255),
  phone       VARCHAR(20),
  location    VARCHAR(255),
  lat_lng     DECIMAL(10,7),
  created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 2) ADMIN TABLE
CREATE TABLE admin (
  id          BIGSERIAL PRIMARY KEY,
  email       VARCHAR(255) UNIQUE NOT NULL,
  password    VARCHAR(255) NOT NULL,
  name        VARCHAR(100),
  is_super    BOOLEAN   NOT NULL DEFAULT FALSE,
  created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at  TIMESTAMP NOT NULL DEFAULT NOW()
);


-- 3) VENDOR TABLE (Store/Restaurants)
CREATE TABLE vendor (
  id          BIGSERIAL PRIMARY KEY,
  email       VARCHAR(255) UNIQUE NOT NULL,
  password    VARCHAR(255) NOT NULL,
  name        VARCHAR(100) NOT NULL,
  phone       VARCHAR(20),
  address     VARCHAR(255),
  food_types  TEXT[],               -- array of strings
  rating      NUMERIC(3,2) DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 4) FOOD TABLE (Menu items)
CREATE TABLE food (
  id          BIGSERIAL PRIMARY KEY,
  vendor_id   BIGINT NOT NULL REFERENCES vendor(id) ON DELETE CASCADE,
  name        VARCHAR(100) NOT NULL,
  description TEXT,
  category    VARCHAR(50),
  price       NUMERIC(10,2) NOT NULL CHECK (price >= 0),
  ready_time  INTEGER,               -- in minutes
  rating      NUMERIC(3,2) DEFAULT 0,
  images      TEXT[],                -- array of image URLs
  created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at  TIMESTAMP NOT NULL DEFAULT NOW()
);


-- 5) ORDER_STATUSES LOOKUP TABLE
CREATE TABLE order_statuses (
  id          BIGSERIAL PRIMARY KEY,
  key         VARCHAR(50) UNIQUE NOT NULL,
  label       VARCHAR(100) NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 6) ORDERS TABLE
CREATE TABLE orders (
  id            BIGSERIAL PRIMARY KEY,
  customer_id   BIGINT NOT NULL REFERENCES customer(id) ON DELETE CASCADE,
  status_id     BIGINT NOT NULL REFERENCES order_statuses(id),
  total_amount  NUMERIC(12,2) NOT NULL,
  remarks       TEXT,
  created_at    TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at    TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 7) FOOD_ORDER (DETAIL) TABLE
CREATE TABLE food_order (
  id          BIGSERIAL PRIMARY KEY,
  food_id     BIGINT NOT NULL REFERENCES food(id) ON DELETE CASCADE,
  order_id    BIGINT NOT NULL REFERENCES orders(id) ON DELETE CASCADE,
  price       NUMERIC(10,2) NOT NULL,
  quantity    NUMERIC(10,2) NOT NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at  TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 8) PAYMENT_METHOD TABLE
CREATE TABLE payment_method (
  id             BIGSERIAL PRIMARY KEY,
  customer_id    BIGINT NOT NULL REFERENCES customer(id) ON DELETE CASCADE,
  stripe_pm_id   VARCHAR(255) NOT NULL,
  type           VARCHAR(50),    -- e.g. “card”, “cod”
  card_brand     VARCHAR(50),
  card_last4     CHAR(4),
  exp_month      INTEGER,
  exp_year       INTEGER,
  created_at     TIMESTAMP NOT NULL DEFAULT NOW()
);

-- 9) PAYMENT TABLE
CREATE TABLE payment (
  id                 BIGSERIAL PRIMARY KEY,
  order_id           BIGINT NOT NULL REFERENCES orders(id),
  payment_method_id  BIGINT NOT NULL REFERENCES payment_method(id),
  stripe_payment_id  VARCHAR(255) UNIQUE NOT NULL,
  amount             NUMERIC(12,2) NOT NULL,
  currency           VARCHAR(10) NOT NULL DEFAULT 'usd',
  status             VARCHAR(50) NOT NULL,
  created_at         TIMESTAMP NOT NULL DEFAULT NOW(),
  updated_at         TIMESTAMP NOT NULL DEFAULT NOW()
);