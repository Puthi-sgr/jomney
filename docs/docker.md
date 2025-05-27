*To get into a container do this:*
docker exec -it <container_id> /bin/bash

*To get a list of containers do this:*
docker ps -a


CREATE TABLE payments (
  id              SERIAL PRIMARY KEY,
  order_id        INTEGER NOT NULL REFERENCES orders(id),
  stripe_payment_id VARCHAR(255) UNIQUE NOT NULL,
  amount          NUMERIC(10,2) NOT NULL,
  currency        VARCHAR(10)    NOT NULL DEFAULT 'usd',
  status          VARCHAR(50)    NOT NULL,           -- e.g. 'requires_payment_method', 'succeeded'
  created_at      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

