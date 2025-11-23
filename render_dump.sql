--
-- PostgreSQL database dump
--

-- Dumped from database version 13.21 (Debian 13.21-1.pgdg120+1)
-- Dumped by pg_dump version 13.21 (Debian 13.21-1.pgdg120+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

ALTER TABLE IF EXISTS ONLY public.payment DROP CONSTRAINT IF EXISTS payment_payment_method_id_fkey;
ALTER TABLE IF EXISTS ONLY public.payment_method DROP CONSTRAINT IF EXISTS payment_method_customer_id_fkey;
ALTER TABLE IF EXISTS ONLY public.orders DROP CONSTRAINT IF EXISTS orders_status_id_fkey;
ALTER TABLE IF EXISTS ONLY public.orders DROP CONSTRAINT IF EXISTS orders_customer_id_fkey;
ALTER TABLE IF EXISTS ONLY public.inventory DROP CONSTRAINT IF EXISTS inventory_food_id_fkey;
ALTER TABLE IF EXISTS ONLY public.food DROP CONSTRAINT IF EXISTS food_vendor_id_fkey;
ALTER TABLE IF EXISTS ONLY public.food_order DROP CONSTRAINT IF EXISTS food_order_food_id_fkey;
DROP INDEX IF EXISTS public.idx_inventory_food;
ALTER TABLE IF EXISTS ONLY public.vendor DROP CONSTRAINT IF EXISTS vendor_pkey;
ALTER TABLE IF EXISTS ONLY public.vendor DROP CONSTRAINT IF EXISTS vendor_email_key;
ALTER TABLE IF EXISTS ONLY public.payment DROP CONSTRAINT IF EXISTS payment_stripe_payment_id_key;
ALTER TABLE IF EXISTS ONLY public.payment DROP CONSTRAINT IF EXISTS payment_pkey;
ALTER TABLE IF EXISTS ONLY public.payment_method DROP CONSTRAINT IF EXISTS payment_method_pkey;
ALTER TABLE IF EXISTS ONLY public.orders DROP CONSTRAINT IF EXISTS orders_pkey;
ALTER TABLE IF EXISTS ONLY public.order_statuses DROP CONSTRAINT IF EXISTS order_statuses_pkey;
ALTER TABLE IF EXISTS ONLY public.order_statuses DROP CONSTRAINT IF EXISTS order_statuses_key_key;
ALTER TABLE IF EXISTS ONLY public.inventory DROP CONSTRAINT IF EXISTS inventory_pkey;
ALTER TABLE IF EXISTS ONLY public.food DROP CONSTRAINT IF EXISTS food_pkey;
ALTER TABLE IF EXISTS ONLY public.food_order DROP CONSTRAINT IF EXISTS food_order_pkey;
ALTER TABLE IF EXISTS ONLY public.customer DROP CONSTRAINT IF EXISTS customer_pkey;
ALTER TABLE IF EXISTS ONLY public.customer DROP CONSTRAINT IF EXISTS customer_email_key;
ALTER TABLE IF EXISTS ONLY public.admin DROP CONSTRAINT IF EXISTS admin_pkey;
ALTER TABLE IF EXISTS ONLY public.admin DROP CONSTRAINT IF EXISTS admin_email_key;
ALTER TABLE IF EXISTS public.vendor ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.payment_method ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.payment ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.orders ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.order_statuses ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.inventory ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.food_order ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.food ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.customer ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.admin ALTER COLUMN id DROP DEFAULT;
DROP SEQUENCE IF EXISTS public.vendor_id_seq;
DROP TABLE IF EXISTS public.vendor;
DROP SEQUENCE IF EXISTS public.payment_method_id_seq;
DROP TABLE IF EXISTS public.payment_method;
DROP SEQUENCE IF EXISTS public.payment_id_seq;
DROP TABLE IF EXISTS public.payment;
DROP SEQUENCE IF EXISTS public.orders_id_seq;
DROP TABLE IF EXISTS public.orders;
DROP SEQUENCE IF EXISTS public.order_statuses_id_seq;
DROP TABLE IF EXISTS public.order_statuses;
DROP SEQUENCE IF EXISTS public.inventory_id_seq;
DROP TABLE IF EXISTS public.inventory;
DROP SEQUENCE IF EXISTS public.food_order_id_seq;
DROP TABLE IF EXISTS public.food_order;
DROP SEQUENCE IF EXISTS public.food_id_seq;
DROP TABLE IF EXISTS public.food;
DROP SEQUENCE IF EXISTS public.customer_id_seq;
DROP TABLE IF EXISTS public.customer;
DROP SEQUENCE IF EXISTS public.admin_id_seq;
DROP TABLE IF EXISTS public.admin;
DROP FUNCTION IF EXISTS public.sync_updated_at();
--
-- Name: sync_updated_at(); Type: FUNCTION; Schema: public; Owner: food_user
--

CREATE FUNCTION public.sync_updated_at() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$;


ALTER FUNCTION public.sync_updated_at() OWNER TO food_user;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: admin; Type: TABLE; Schema: public; Owner: food_user
--

CREATE TABLE public.admin (
    id bigint NOT NULL,
    email character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    name character varying(100),
    is_super boolean DEFAULT false NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.admin OWNER TO food_user;

--
-- Name: admin_id_seq; Type: SEQUENCE; Schema: public; Owner: food_user
--

CREATE SEQUENCE public.admin_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.admin_id_seq OWNER TO food_user;

--
-- Name: admin_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: food_user
--

ALTER SEQUENCE public.admin_id_seq OWNED BY public.admin.id;


--
-- Name: customer; Type: TABLE; Schema: public; Owner: food_user
--

CREATE TABLE public.customer (
    id bigint NOT NULL,
    email character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    name character varying(100) NOT NULL,
    address character varying(255),
    phone character varying(20),
    location character varying(255),
    lat_lng numeric(10,7),
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    image character varying(255),
    stripe_customer_id character varying(255)
);


ALTER TABLE public.customer OWNER TO food_user;

--
-- Name: customer_id_seq; Type: SEQUENCE; Schema: public; Owner: food_user
--

CREATE SEQUENCE public.customer_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.customer_id_seq OWNER TO food_user;

--
-- Name: customer_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: food_user
--

ALTER SEQUENCE public.customer_id_seq OWNED BY public.customer.id;


--
-- Name: food; Type: TABLE; Schema: public; Owner: food_user
--

CREATE TABLE public.food (
    id bigint NOT NULL,
    vendor_id bigint NOT NULL,
    name character varying(100) NOT NULL,
    description text,
    category character varying(50),
    price numeric(10,2) NOT NULL,
    ready_time integer,
    rating numeric(3,2) DEFAULT 0,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    image text,
    CONSTRAINT food_price_check CHECK ((price >= (0)::numeric))
);


ALTER TABLE public.food OWNER TO food_user;

--
-- Name: food_id_seq; Type: SEQUENCE; Schema: public; Owner: food_user
--

CREATE SEQUENCE public.food_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.food_id_seq OWNER TO food_user;

--
-- Name: food_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: food_user
--

ALTER SEQUENCE public.food_id_seq OWNED BY public.food.id;


--
-- Name: food_order; Type: TABLE; Schema: public; Owner: food_user
--

CREATE TABLE public.food_order (
    id bigint NOT NULL,
    food_id bigint NOT NULL,
    order_id bigint NOT NULL,
    price numeric(10,2) NOT NULL,
    quantity numeric(10,2) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.food_order OWNER TO food_user;

--
-- Name: food_order_id_seq; Type: SEQUENCE; Schema: public; Owner: food_user
--

CREATE SEQUENCE public.food_order_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.food_order_id_seq OWNER TO food_user;

--
-- Name: food_order_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: food_user
--

ALTER SEQUENCE public.food_order_id_seq OWNED BY public.food_order.id;


--
-- Name: inventory; Type: TABLE; Schema: public; Owner: food_user
--

CREATE TABLE public.inventory (
    id bigint NOT NULL,
    food_id bigint NOT NULL,
    qty_available integer DEFAULT 0 NOT NULL,
    updated_at timestamp without time zone DEFAULT CURRENT_TIMESTAMP
);


ALTER TABLE public.inventory OWNER TO food_user;

--
-- Name: inventory_id_seq; Type: SEQUENCE; Schema: public; Owner: food_user
--

CREATE SEQUENCE public.inventory_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.inventory_id_seq OWNER TO food_user;

--
-- Name: inventory_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: food_user
--

ALTER SEQUENCE public.inventory_id_seq OWNED BY public.inventory.id;


--
-- Name: order_statuses; Type: TABLE; Schema: public; Owner: food_user
--

CREATE TABLE public.order_statuses (
    id bigint NOT NULL,
    key character varying(50) NOT NULL,
    label character varying(100) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.order_statuses OWNER TO food_user;

--
-- Name: order_statuses_id_seq; Type: SEQUENCE; Schema: public; Owner: food_user
--

CREATE SEQUENCE public.order_statuses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.order_statuses_id_seq OWNER TO food_user;

--
-- Name: order_statuses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: food_user
--

ALTER SEQUENCE public.order_statuses_id_seq OWNED BY public.order_statuses.id;


--
-- Name: orders; Type: TABLE; Schema: public; Owner: food_user
--

CREATE TABLE public.orders (
    id bigint NOT NULL,
    customer_id bigint NOT NULL,
    status_id bigint NOT NULL,
    total_amount numeric(12,2) NOT NULL,
    remarks text,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.orders OWNER TO food_user;

--
-- Name: orders_id_seq; Type: SEQUENCE; Schema: public; Owner: food_user
--

CREATE SEQUENCE public.orders_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.orders_id_seq OWNER TO food_user;

--
-- Name: orders_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: food_user
--

ALTER SEQUENCE public.orders_id_seq OWNED BY public.orders.id;


--
-- Name: payment; Type: TABLE; Schema: public; Owner: food_user
--

CREATE TABLE public.payment (
    id bigint NOT NULL,
    order_id bigint NOT NULL,
    payment_method_id bigint NOT NULL,
    stripe_payment_id character varying(255) NOT NULL,
    amount numeric(12,2) NOT NULL,
    currency character varying(10) DEFAULT 'usd'::character varying NOT NULL,
    status character varying(50) NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.payment OWNER TO food_user;

--
-- Name: payment_id_seq; Type: SEQUENCE; Schema: public; Owner: food_user
--

CREATE SEQUENCE public.payment_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.payment_id_seq OWNER TO food_user;

--
-- Name: payment_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: food_user
--

ALTER SEQUENCE public.payment_id_seq OWNED BY public.payment.id;


--
-- Name: payment_method; Type: TABLE; Schema: public; Owner: food_user
--

CREATE TABLE public.payment_method (
    id bigint NOT NULL,
    customer_id bigint NOT NULL,
    stripe_pm_id character varying(255) NOT NULL,
    type character varying(50),
    card_brand character varying(50),
    card_last4 character(4),
    exp_month integer,
    exp_year integer,
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.payment_method OWNER TO food_user;

--
-- Name: payment_method_id_seq; Type: SEQUENCE; Schema: public; Owner: food_user
--

CREATE SEQUENCE public.payment_method_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.payment_method_id_seq OWNER TO food_user;

--
-- Name: payment_method_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: food_user
--

ALTER SEQUENCE public.payment_method_id_seq OWNED BY public.payment_method.id;


--
-- Name: vendor; Type: TABLE; Schema: public; Owner: food_user
--

CREATE TABLE public.vendor (
    id bigint NOT NULL,
    email character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    name character varying(100) NOT NULL,
    phone character varying(20),
    address character varying(255),
    food_types jsonb,
    rating numeric(3,2) DEFAULT 0,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    image text
);


ALTER TABLE public.vendor OWNER TO food_user;

--
-- Name: vendor_id_seq; Type: SEQUENCE; Schema: public; Owner: food_user
--

CREATE SEQUENCE public.vendor_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.vendor_id_seq OWNER TO food_user;

--
-- Name: vendor_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: food_user
--

ALTER SEQUENCE public.vendor_id_seq OWNED BY public.vendor.id;


--
-- Name: admin id; Type: DEFAULT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.admin ALTER COLUMN id SET DEFAULT nextval('public.admin_id_seq'::regclass);


--
-- Name: customer id; Type: DEFAULT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.customer ALTER COLUMN id SET DEFAULT nextval('public.customer_id_seq'::regclass);


--
-- Name: food id; Type: DEFAULT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.food ALTER COLUMN id SET DEFAULT nextval('public.food_id_seq'::regclass);


--
-- Name: food_order id; Type: DEFAULT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.food_order ALTER COLUMN id SET DEFAULT nextval('public.food_order_id_seq'::regclass);


--
-- Name: inventory id; Type: DEFAULT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.inventory ALTER COLUMN id SET DEFAULT nextval('public.inventory_id_seq'::regclass);


--
-- Name: order_statuses id; Type: DEFAULT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.order_statuses ALTER COLUMN id SET DEFAULT nextval('public.order_statuses_id_seq'::regclass);


--
-- Name: orders id; Type: DEFAULT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.orders ALTER COLUMN id SET DEFAULT nextval('public.orders_id_seq'::regclass);


--
-- Name: payment id; Type: DEFAULT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.payment ALTER COLUMN id SET DEFAULT nextval('public.payment_id_seq'::regclass);


--
-- Name: payment_method id; Type: DEFAULT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.payment_method ALTER COLUMN id SET DEFAULT nextval('public.payment_method_id_seq'::regclass);


--
-- Name: vendor id; Type: DEFAULT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.vendor ALTER COLUMN id SET DEFAULT nextval('public.vendor_id_seq'::regclass);


--
-- Data for Name: admin; Type: TABLE DATA; Schema: public; Owner: food_user
--

COPY public.admin (id, email, password, name, is_super, created_at, updated_at) FROM stdin;
2	systemAdmin@example.com	$2y$10$CRzGcfQhE4uEu2Q4wQdOz.7cvCMVQMxofOu4w/jWHigxifDaiYn/S	System Admin	t	2025-06-08 04:14:51.572803	2025-06-08 04:14:51.572803
1	admin@example.com	SuperSecure123	\N	f	2025-06-08 03:39:46.628627	2025-06-08 03:39:46.628627
\.


--
-- Data for Name: customer; Type: TABLE DATA; Schema: public; Owner: food_user
--

COPY public.customer (id, email, password, name, address, phone, location, lat_lng, created_at, updated_at, image, stripe_customer_id) FROM stdin;
26	gege@gmail.com	$2y$10$tHQCZwGC2GtZ6kcEkQqS1ux/7b3QaQlN1PZ1fL9E90I7l1IWdJeOS	Thi Scammerrrr	Phnom Penh\nPhnom Penh	+1-555-0005	Here	\N	2025-07-02 13:15:18.371187	2025-07-02 13:15:18.371187	\N	cus_SbeYnwCQWKfdAH
29	kdeypong@gmail.com	$2y$10$rvkI5j7q/EgrIv99htB52OEJ/XN.1/3VCjvRwaR2x97TeFnlGDWrC	Pheak Kdey	\N	\N	\N	\N	2025-07-06 07:53:59.211257	2025-07-06 07:53:59.211257	\N	\N
30	sokha.suk2007@gmail.com	$2y$10$PlmbXQZC9si1XNeCfyilw.A21yWjl2m61CHFWXUcSchdlqHC7E/6i	Sokha Suk 	\N	\N	\N	\N	2025-07-06 13:23:23.307364	2025-07-06 13:23:23.307364	\N	\N
40	puthi1239@gmail.com	$2y$10$AQyoEjFmzMFWbexhugoWcePwWL2vgmFGfNZ/Zc9gYRJ4MHd9bEATS	PuthiGuy		0975059272	\N	\N	2025-07-13 04:21:33.851928	2025-07-13 04:24:52.176771	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1752380690/foodDelivery/customers/customer-40/vmw7f4xvnzq1cxitblyh.jpg	cus_SfcpICJGI2ydeP
37	puthi@gmail.com	$2y$10$2KRVUXjupfzZ0HfRCpfA9ODZygj9My0El7nN5h5KtbNi7o2zyF9hS	Puthizin	Phnom Penh\nPhnom Penh	0975059272	Here	\N	2025-07-08 06:18:51.559682	2025-07-08 06:43:21.656677	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1751957000/foodDelivery/customers/customer-37/l46gqpy6sxl4ccw3etct.jpg	cus_SdmSadhI6TYA5O
41	sokley@gmail.com	$2y$10$ujocGclur8vTZZr/5h8uxeSXbZ3RRSVo/CChaWYT/Xzf4fOra2cJ2	sokley	Phnom Penh\nPhnom Penh	0975059272	\N	\N	2025-07-29 08:51:48.496112	2025-09-25 08:09:36.912418	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1758787774/foodDelivery/customers/customer-41/gn2pcw7r97qzkevtgvnb.png	cus_T7OngfG53OTuhT
42	myfull@gmail.com	$2y$10$j23RrGrntSDZG3jluEVLduMq.On1RwghJRnHux/gO52HUM8qBYS9S	myfullname	\N	\N	\N	\N	2025-09-25 08:31:51.285874	2025-09-25 08:31:51.285874	\N	cus_T7QnK2JYSPz1QT
36	puthipong@gmail.com	$2y$10$HLY/0fcPzQ1n8iZxgwGrS.pBPdtg1zKiOvDuSNrBdC9ih4O38ApJS	Puthi Pong	\N	\N	\N	\N	2025-07-08 04:48:35.166238	2025-07-08 04:48:35.166238	\N	cus_Sdky49DOgleZMk
43	webnokor@gmail.com	$2y$10$ucBugsbH5q9UH6cCp1/IzOdNM2lymat1Hqx9c32YYDjdZUxkUBrVm	Puthi	\N	\N	\N	\N	2025-09-26 06:41:48.143812	2025-09-26 06:41:48.143812	\N	cus_T7kr2WVhGzDBUr
44	chethapuhjjj@gmail.com	$2y$10$ha.s8g5t79eog2U3RXGSNeCEFjqojtTv9D0JDKKL31FgSG.7Zk7/K	Customerv2	\N	\N	\N	\N	2025-09-26 06:55:00.97857	2025-09-26 06:55:00.97857	\N	cus_T7l56Ks5sfCJ2O
35	tainuth@gmail.com	$2y$10$SmFBBqVwe6kIL4/z11juQuyxE8FkIGmbdhZ3nfjJtdMKCCNWEBMMu	Tai Nuth	\N	\N	\N	\N	2025-07-08 04:19:12.522821	2025-07-12 09:20:26.500324	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1752312025/foodDelivery/customers/customer-35/brmnqcqhtzlnluohyozc.jpg	cus_SdkXezqi6bNDH5
39	exampledemo@gmail.com	$2y$10$uB8rTgL5ARXhE6vk/ka/gOj.FghGNQChMgknWos5U0jd6Vy6TqEvG	Puthi	\N	\N	\N	\N	2025-07-12 10:52:29.846149	2025-07-12 10:52:29.846149	\N	\N
45	vital@gmail.com	$2y$10$hybRY4QrgS4VLRQj86yOku0QRs6aG3kHHmV6VQs5lAnR1s/gBs.1.	Vital		9077		\N	2025-09-29 06:56:50.807477	2025-09-29 06:56:50.807477	\N	cus_T8snu9ewrYgJMq
\.


--
-- Data for Name: food; Type: TABLE DATA; Schema: public; Owner: food_user
--

COPY public.food (id, vendor_id, name, description, category, price, ready_time, rating, created_at, updated_at, image) FROM stdin;
29	30	Kampot Pepper Crab	Famous for its Kampot pepper crab	Dish	25.00	10	4.50	2025-07-06 06:20:34.976313	2025-07-06 06:20:34.976313	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1751782837/foodDelivery/vendors/vendor-30/food-29/image/qganc7khcanbxoccl3ss.webp
32	40	Burger	Berger	Burger	10.00	8	4.50	2025-07-12 10:01:19.424935	2025-07-12 10:01:19.424935	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1752314482/foodDelivery/vendors/vendor-40/food-32/image/m2tydz6xc3s7klag1v8k.jpg
22	16	Starbuck Coffee	You fell as sleep? Buy some coffee bro	Coffee	4.50	4	4.50	2025-07-04 03:49:55.219299	2025-07-04 03:49:55.219299	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1751600997/foodDelivery/vendors/vendor-16/food-22/image/vkgkr13zlb56wtajpkos.jpg
23	29	នំបញ្ចុក​ខ្មែរ	Enjoy khmer food	Breakfirst	1.50	5	4.40	2025-07-04 14:44:59.504694	2025-07-04 14:44:59.504694	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1751640302/foodDelivery/vendors/vendor-29/food-23/image/nykxa9lijv9p9yzfay7z.jpg
24	30	Ahmok Khmer	Try our menu ahmok khmer.	Foods	7.50	15	4.90	2025-07-04 14:47:41.261706	2025-07-04 14:47:41.261706	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1751640464/foodDelivery/vendors/vendor-30/food-24/image/wemvjn4t06nvr8ezskuk.jpg
25	20	Coffee Latte	Our new coffee flavour	Coffee	5.00	8	4.50	2025-07-04 14:49:53.176289	2025-07-04 14:49:53.176289	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1751640596/foodDelivery/vendors/vendor-20/food-25/image/yhugwlmplfogg0b0u5cr.webp
26	19	Cheese  Pizza	New Pizza in our menu, try it out.	Pizza	15.00	20	4.40	2025-07-04 14:55:42.165663	2025-07-04 14:55:42.165663	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1751640944/foodDelivery/vendors/vendor-19/food-26/image/zh3n5ydmontp59h5iosn.jpg
27	29	Lok lak	Lok lak is a traditional Cambodian dish	Foods	2.50	15	4.50	2025-07-05 10:30:26.18625	2025-07-05 10:30:26.18625	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1751711429/foodDelivery/vendors/vendor-29/food-27/image/hituyvnrvzkr8sehavki.jpg
28	30	Samlor kari	Samlor kari is typically made with chicken	Soup	5.50	15	4.10	2025-07-05 10:32:52.32469	2025-07-05 10:32:52.32469	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1751711575/foodDelivery/vendors/vendor-30/food-28/image/ftdufefzmh0pvbiy2zxv.jpg
\.


--
-- Data for Name: food_order; Type: TABLE DATA; Schema: public; Owner: food_user
--

COPY public.food_order (id, food_id, order_id, price, quantity, created_at, updated_at) FROM stdin;
114	26	100	15.00	1.00	2025-09-26 07:56:45.133902	2025-09-26 07:56:45.133902
115	26	101	15.00	2.00	2025-09-29 06:57:38.05548	2025-09-29 06:57:38.05548
20	24	24	7.50	1.00	2025-07-06 13:54:27.764188	2025-07-06 13:54:27.764188
28	25	32	5.00	1.00	2025-07-06 16:03:28.615954	2025-07-06 16:03:28.615954
44	29	48	25.00	5.00	2025-07-07 16:27:49.745344	2025-07-07 16:27:49.745344
45	26	49	15.00	2.00	2025-07-07 16:37:35.98439	2025-07-07 16:37:35.98439
46	22	49	4.50	1.00	2025-07-07 16:37:35.98439	2025-07-07 16:37:35.98439
47	29	49	25.00	1.00	2025-07-07 16:37:35.98439	2025-07-07 16:37:35.98439
49	24	50	7.50	2.00	2025-07-08 04:21:51.663206	2025-07-08 04:21:51.663206
50	24	51	7.50	3.00	2025-07-08 04:26:24.584723	2025-07-08 04:26:24.584723
51	22	52	4.50	1.00	2025-07-08 04:30:47.757346	2025-07-08 04:30:47.757346
52	24	52	7.50	1.00	2025-07-08 04:30:47.757346	2025-07-08 04:30:47.757346
53	22	53	4.50	1.00	2025-07-08 04:42:22.671416	2025-07-08 04:42:22.671416
54	24	53	7.50	1.00	2025-07-08 04:42:22.671416	2025-07-08 04:42:22.671416
55	27	54	2.50	2.00	2025-07-08 04:49:04.539788	2025-07-08 04:49:04.539788
56	28	54	5.50	2.00	2025-07-08 04:49:04.539788	2025-07-08 04:49:04.539788
57	29	55	25.00	2.00	2025-07-08 05:21:50.394545	2025-07-08 05:21:50.394545
59	29	56	25.00	2.00	2025-07-08 06:02:53.200007	2025-07-08 06:02:53.200007
60	23	57	1.50	5.00	2025-07-08 06:21:00.909722	2025-07-08 06:21:00.909722
61	27	57	2.50	8.00	2025-07-08 06:21:00.909722	2025-07-08 06:21:00.909722
62	24	58	7.50	1.00	2025-07-08 06:37:16.643361	2025-07-08 06:37:16.643361
63	29	58	25.00	1.00	2025-07-08 06:37:16.643361	2025-07-08 06:37:16.643361
64	24	59	7.50	1.00	2025-07-08 06:38:03.457592	2025-07-08 06:38:03.457592
65	29	59	25.00	1.00	2025-07-08 06:38:03.457592	2025-07-08 06:38:03.457592
66	29	60	25.00	1.00	2025-07-08 06:38:27.000883	2025-07-08 06:38:27.000883
67	24	61	7.50	1.00	2025-07-08 06:39:16.182851	2025-07-08 06:39:16.182851
68	28	61	5.50	6.00	2025-07-08 06:39:16.182851	2025-07-08 06:39:16.182851
69	22	62	4.50	2.00	2025-07-12 09:22:47.468341	2025-07-12 09:22:47.468341
70	26	62	15.00	2.00	2025-07-12 09:22:47.468341	2025-07-12 09:22:47.468341
71	29	62	25.00	2.00	2025-07-12 09:22:47.468341	2025-07-12 09:22:47.468341
72	26	63	15.00	5.00	2025-07-12 09:23:34.797709	2025-07-12 09:23:34.797709
73	24	64	7.50	2.00	2025-07-12 09:26:52.294674	2025-07-12 09:26:52.294674
75	23	64	1.50	2.00	2025-07-12 09:26:52.294674	2025-07-12 09:26:52.294674
76	25	65	5.00	1.00	2025-07-13 04:31:25.046445	2025-07-13 04:31:25.046445
77	25	66	5.00	1.00	2025-07-13 04:32:10.668674	2025-07-13 04:32:10.668674
78	25	67	5.00	1.00	2025-07-13 04:33:22.479469	2025-07-13 04:33:22.479469
79	25	68	5.00	5.00	2025-07-13 04:41:12.601998	2025-07-13 04:41:12.601998
94	29	83	25.00	1.00	2025-09-25 07:53:15.729013	2025-09-25 07:53:15.729013
95	29	84	25.00	1.00	2025-09-25 07:53:41.129646	2025-09-25 07:53:41.129646
96	29	85	25.00	1.00	2025-09-25 08:05:53.270414	2025-09-25 08:05:53.270414
97	26	86	15.00	3.00	2025-09-25 08:12:11.183619	2025-09-25 08:12:11.183619
98	28	87	5.50	1.00	2025-09-25 08:23:46.381863	2025-09-25 08:23:46.381863
100	32	89	10.00	1.00	2025-09-25 08:32:19.589146	2025-09-25 08:32:19.589146
101	27	90	2.50	1.00	2025-09-25 08:41:47.512009	2025-09-25 08:41:47.512009
102	27	91	2.50	1.00	2025-09-25 08:44:42.488458	2025-09-25 08:44:42.488458
103	27	92	2.50	1.00	2025-09-25 08:45:15.383465	2025-09-25 08:45:15.383465
104	24	92	7.50	4.00	2025-09-25 08:45:15.383465	2025-09-25 08:45:15.383465
107	22	95	4.50	13.00	2025-09-25 14:18:49.81864	2025-09-25 14:18:49.81864
108	25	96	5.00	1.00	2025-09-26 06:42:01.359536	2025-09-26 06:42:01.359536
109	25	97	5.00	1.00	2025-09-26 06:42:47.000243	2025-09-26 06:42:47.000243
110	23	97	1.50	1.00	2025-09-26 06:42:47.000243	2025-09-26 06:42:47.000243
111	25	98	5.00	1.00	2025-09-26 06:48:50.750109	2025-09-26 06:48:50.750109
112	23	98	1.50	1.00	2025-09-26 06:48:50.750109	2025-09-26 06:48:50.750109
113	29	99	25.00	1.00	2025-09-26 06:55:31.310817	2025-09-26 06:55:31.310817
\.


--
-- Data for Name: inventory; Type: TABLE DATA; Schema: public; Owner: food_user
--

COPY public.inventory (id, food_id, qty_available, updated_at) FROM stdin;
26	28	992	2025-09-25 08:23:46.381863
30	32	0	2025-09-25 08:32:19.589146
25	27	988	2025-09-25 08:45:15.383465
22	24	984	2025-09-25 08:45:15.383465
20	22	983	2025-09-25 14:18:49.81864
23	25	989	2025-09-26 06:48:50.750109
21	23	992	2025-09-26 06:48:50.750109
27	29	982	2025-09-26 06:55:31.310817
24	26	986	2025-09-29 06:57:38.05548
\.


--
-- Data for Name: order_statuses; Type: TABLE DATA; Schema: public; Owner: food_user
--

COPY public.order_statuses (id, key, label, created_at, updated_at) FROM stdin;
1	pending	Pending	2025-06-18 15:34:54.463014	2025-06-18 15:34:54.463014
2	accepted	Accepted	2025-06-18 15:34:54.463014	2025-06-18 15:34:54.463014
3	preparing	Preparing	2025-06-18 15:34:54.463014	2025-06-18 15:34:54.463014
4	ready	Ready for Pickup	2025-06-18 15:34:54.463014	2025-06-18 15:34:54.463014
5	on_the_way	On the Way	2025-06-18 15:34:54.463014	2025-06-18 15:34:54.463014
6	delivered	Delivered	2025-06-18 15:34:54.463014	2025-06-18 15:34:54.463014
7	cancelled	Cancelled	2025-06-18 15:34:54.463014	2025-06-18 15:34:54.463014
\.


--
-- Data for Name: orders; Type: TABLE DATA; Schema: public; Owner: food_user
--

COPY public.orders (id, customer_id, status_id, total_amount, remarks, created_at, updated_at) FROM stdin;
55	36	2	70.00	Som Drink	2025-07-08 05:21:50.394545	2025-07-08 05:21:50.394545
56	36	2	50.00	Som By 2-3 chan mk.	2025-07-08 06:02:53.200007	2025-07-08 06:02:53.200007
52	35	2	12.00		2025-07-08 04:30:47.757346	2025-07-08 04:30:47.757346
50	35	2	15.00	Som by ma chan phg.	2025-07-08 04:21:51.663206	2025-07-08 04:21:51.663206
57	37	2	27.50	Test order	2025-07-08 06:21:00.909722	2025-07-08 06:21:00.909722
58	37	1	32.50		2025-07-08 06:37:16.643361	2025-07-08 06:37:16.643361
59	37	1	32.50		2025-07-08 06:38:03.457592	2025-07-08 06:38:03.457592
60	37	2	25.00		2025-07-08 06:38:27.000883	2025-07-08 06:38:27.000883
61	37	2	40.50		2025-07-08 06:39:16.182851	2025-07-08 06:39:16.182851
62	35	2	89.00	Hello	2025-07-12 09:22:47.468341	2025-07-12 09:22:47.468341
63	35	2	75.00	PineApple Source	2025-07-12 09:23:34.797709	2025-07-12 09:23:34.797709
100	41	2	15.00		2025-09-26 07:56:45.133902	2025-09-26 07:56:45.133902
65	40	1	5.00		2025-07-13 04:31:25.046445	2025-07-13 04:31:25.046445
66	40	1	5.00		2025-07-13 04:32:10.668674	2025-07-13 04:32:10.668674
67	40	2	5.00		2025-07-13 04:33:22.479469	2025-07-13 04:33:22.479469
68	40	2	25.00		2025-07-13 04:41:12.601998	2025-07-13 04:41:12.601998
69	41	1	40.00	Make the burger bigger	2025-07-31 03:59:08.217191	2025-07-31 03:59:08.217191
70	41	1	40.00	Make the burger bigger	2025-07-31 03:59:08.372724	2025-07-31 03:59:08.372724
71	41	1	40.00	Make the burger bigger	2025-07-31 04:10:46.616861	2025-07-31 04:10:46.616861
72	41	1	40.00	Make the burger bigger	2025-07-31 04:10:46.695322	2025-07-31 04:10:46.695322
73	41	1	40.00	Make the burger bigger	2025-07-31 04:11:26.821818	2025-07-31 04:11:26.821818
74	41	1	40.00	Make the burger bigger	2025-07-31 04:11:26.86189	2025-07-31 04:11:26.86189
75	41	1	40.00	Make the burger bigger	2025-07-31 04:12:29.014363	2025-07-31 04:12:29.014363
76	41	1	40.00	Make the burger bigger	2025-07-31 04:12:29.019611	2025-07-31 04:12:29.019611
82	41	2	40.00	Make the burger bigger	2025-07-31 04:56:33.316736	2025-07-31 04:56:33.316736
51	35	2	22.50		2025-07-08 04:26:24.584723	2025-07-08 04:26:24.584723
53	35	2	12.00		2025-07-08 04:42:22.671416	2025-07-08 04:42:22.671416
54	36	2	16.00	Some them by tix phg	2025-07-08 04:49:04.539788	2025-07-08 04:49:04.539788
81	41	2	40.00	Make the burger bigger	2025-07-31 04:56:29.45064	2025-07-31 04:56:29.45064
80	41	3	40.00	Make the burger bigger	2025-07-31 04:16:57.883957	2025-07-31 04:16:57.883957
79	41	2	40.00	Make the burger bigger	2025-07-31 04:16:57.876389	2025-07-31 04:16:57.876389
78	41	2	40.00	Make the burger bigger	2025-07-31 04:16:15.438967	2025-07-31 04:16:15.438967
77	41	3	40.00	Make the burger bigger	2025-07-31 04:16:15.408025	2025-07-31 04:16:15.408025
101	45	3	30.00	hhxbvn	2025-09-29 06:57:38.05548	2025-09-29 06:57:38.05548
64	35	6	38.00		2025-07-12 09:26:52.294674	2025-07-12 09:26:52.294674
83	41	2	25.00		2025-09-25 07:53:15.729013	2025-09-25 07:53:15.729013
84	41	2	25.00		2025-09-25 07:53:41.129646	2025-09-25 07:53:41.129646
85	41	2	25.00		2025-09-25 08:05:53.270414	2025-09-25 08:05:53.270414
86	41	2	45.00	make it spicye	2025-09-25 08:12:11.183619	2025-09-25 08:12:11.183619
87	41	2	5.50		2025-09-25 08:23:46.381863	2025-09-25 08:23:46.381863
88	41	2	10.00		2025-09-25 08:30:15.79651	2025-09-25 08:30:15.79651
89	42	1	10.00		2025-09-25 08:32:19.589146	2025-09-25 08:32:19.589146
90	42	1	2.50		2025-09-25 08:41:47.512009	2025-09-25 08:41:47.512009
91	42	1	2.50		2025-09-25 08:44:42.488458	2025-09-25 08:44:42.488458
92	42	1	32.50		2025-09-25 08:45:15.383465	2025-09-25 08:45:15.383465
93	42	1	200.00		2025-09-25 09:57:21.086364	2025-09-25 09:57:21.086364
94	42	2	200.00		2025-09-25 09:57:43.673263	2025-09-25 09:57:43.673263
95	42	2	58.50		2025-09-25 14:18:49.81864	2025-09-25 14:18:49.81864
96	43	1	5.00		2025-09-26 06:42:01.359536	2025-09-26 06:42:01.359536
97	43	1	6.50		2025-09-26 06:42:47.000243	2025-09-26 06:42:47.000243
98	43	2	6.50		2025-09-26 06:48:50.750109	2025-09-26 06:48:50.750109
99	44	2	25.00		2025-09-26 06:55:31.310817	2025-09-26 06:55:31.310817
\.


--
-- Data for Name: payment; Type: TABLE DATA; Schema: public; Owner: food_user
--

COPY public.payment (id, order_id, payment_method_id, stripe_payment_id, amount, currency, status, created_at, updated_at) FROM stdin;
9	53	13	pi_3RiTMsQIkr1RTuse1IqjFoCC	12.00	usd	succeeded	2025-07-08 04:42:59.762655	2025-07-08 04:42:59.762655
10	54	14	pi_3RiTT8QIkr1RTuse1oC9Z89d	16.00	usd	succeeded	2025-07-08 04:49:27.687796	2025-07-08 04:49:27.687796
11	55	14	pi_3RiUE6QIkr1RTuse0iyOzprr	70.00	usd	succeeded	2025-07-08 05:38:01.128632	2025-07-08 05:38:01.128632
12	56	14	pi_3RiUcIQIkr1RTuse1cwhSyAA	50.00	usd	succeeded	2025-07-08 06:03:00.304195	2025-07-08 06:03:00.304195
13	52	13	pi_3RiUdTQIkr1RTuse0rgqpFwE	12.00	usd	succeeded	2025-07-08 06:04:12.878682	2025-07-08 06:04:12.878682
14	50	13	pi_3RiUdlQIkr1RTuse0rXeUrRV	15.00	usd	succeeded	2025-07-08 06:04:32.374198	2025-07-08 06:04:32.374198
15	57	15	pi_3RiUupQIkr1RTuse1pT0AfB3	27.50	usd	succeeded	2025-07-08 06:22:09.228558	2025-07-08 06:22:09.228558
16	60	15	pi_3RiVAiQIkr1RTuse1vv6fifW	25.00	usd	succeeded	2025-07-08 06:38:34.162272	2025-07-08 06:38:34.162272
17	61	15	pi_3RiVBWQIkr1RTuse05DRR994	40.50	usd	succeeded	2025-07-08 06:39:23.814328	2025-07-08 06:39:23.814328
18	62	13	pi_3RjzdvQIkr1RTuse19OPXind	89.00	usd	succeeded	2025-07-12 09:22:53.843686	2025-07-12 09:22:53.843686
19	63	13	pi_3RjzevQIkr1RTuse1Io7KjJj	75.00	usd	succeeded	2025-07-12 09:23:54.931791	2025-07-12 09:23:54.931791
20	64	13	pi_3RjzhsQIkr1RTuse1GXccNgB	38.00	usd	succeeded	2025-07-12 09:26:57.787137	2025-07-12 09:26:57.787137
21	67	16	pi_3RkHbsQIkr1RTuse1A6xrQrE	5.00	usd	succeeded	2025-07-13 04:34:00.942794	2025-07-13 04:34:00.942794
22	68	16	pi_3RkHjOQIkr1RTuse0n2pkDcY	25.00	usd	succeeded	2025-07-13 04:41:45.647804	2025-07-13 04:41:45.647804
23	83	17	pi_3SB9zfQIkr1RTuse1ES409aV	25.00	usd	succeeded	2025-09-25 07:53:36.793236	2025-09-25 07:53:36.793236
24	84	18	pi_3SBA1LQIkr1RTuse1hSsNv8Y	25.00	usd	succeeded	2025-09-25 07:55:20.407302	2025-09-25 07:55:20.407302
25	85	18	pi_3SBABfQIkr1RTuse0iohlR9D	25.00	usd	succeeded	2025-09-25 08:06:01.541003	2025-09-25 08:06:01.541003
26	86	18	pi_3SBAHiQIkr1RTuse13rCyIrz	45.00	usd	succeeded	2025-09-25 08:12:15.869643	2025-09-25 08:12:15.869643
27	87	18	pi_3SBASvQIkr1RTuse16FXiAfe	5.50	usd	succeeded	2025-09-25 08:23:51.287251	2025-09-25 08:23:51.287251
28	88	18	pi_3SBAZCQIkr1RTuse0yiIxsUH	10.00	usd	succeeded	2025-09-25 08:30:20.916818	2025-09-25 08:30:20.916818
29	94	19	pi_3SBBw5QIkr1RTuse0qljmm0H	200.00	usd	succeeded	2025-09-25 09:58:03.811198	2025-09-25 09:58:03.811198
30	95	19	pi_3SBG0XQIkr1RTuse0crOVH9A	58.50	usd	succeeded	2025-09-25 14:18:55.506674	2025-09-25 14:18:55.506674
31	98	20	pi_3SBVTIQIkr1RTuse13nE1bEA	6.50	usd	succeeded	2025-09-26 06:49:38.046504	2025-09-26 06:49:38.046504
32	99	21	pi_3SBVZSQIkr1RTuse1r8IAHEe	25.00	usd	succeeded	2025-09-26 06:55:59.616926	2025-09-26 06:55:59.616926
33	100	22	pi_3SBWXCQIkr1RTuse1toq15Ia	15.00	usd	succeeded	2025-09-26 07:57:43.974548	2025-09-26 07:57:43.974548
34	101	23	pi_3SCb3DQIkr1RTuse1PmQAZ3u	30.00	usd	succeeded	2025-09-29 06:59:13.111973	2025-09-29 06:59:13.111973
\.


--
-- Data for Name: payment_method; Type: TABLE DATA; Schema: public; Owner: food_user
--

COPY public.payment_method (id, customer_id, stripe_pm_id, type, card_brand, card_last4, exp_month, exp_year, created_at) FROM stdin;
13	35	pm_1RiTMmQIkr1RTusePQiU9CHi	card	visa	4242	4	2029	2025-07-08 04:42:56.601134
14	36	pm_1RiTT2QIkr1RTuseE9pp4zhx	card	visa	4242	4	2029	2025-07-08 04:49:24.394815
15	37	pm_1RiUuhQIkr1RTusePpzpbtXH	card	visa	4242	12	2025	2025-07-08 06:22:03.832825
16	40	pm_1RkHbgQIkr1RTuseHuXwspM0	card	visa	4242	12	2028	2025-07-13 04:33:52.967391
17	41	pm_1SB9zaQIkr1RTuseYzAO2jIM	card	visa	4242	12	2028	2025-09-25 07:53:34.707433
18	41	pm_1SBA1GQIkr1RTuseo4nBPpvM	card	visa	4242	12	2025	2025-09-25 07:55:18.119417
19	42	pm_1SBBvzQIkr1RTusedbSnsa4Z	card	visa	4242	12	2028	2025-09-25 09:58:00.232534
20	43	pm_1SBVT5QIkr1RTusev4iIkHk0	card	visa	4242	12	2025	2025-09-26 06:49:33.489971
21	44	pm_1SBVZNQIkr1RTuse3jxfL9uG	card	visa	4242	12	2025	2025-09-26 06:55:57.744318
22	41	pm_1SBWX8QIkr1RTusehGiy3P47	card	visa	4242	12	2025	2025-09-26 07:57:42.415014
23	45	pm_1SCb33QIkr1RTuseWoiXli6Z	card	visa	4242	12	2025	2025-09-29 06:59:08.115972
\.


--
-- Data for Name: vendor; Type: TABLE DATA; Schema: public; Owner: food_user
--

COPY public.vendor (id, email, password, name, phone, address, food_types, rating, created_at, updated_at, image) FROM stdin;
15	amazon@coffee.com	$2y$10$NzWXpWVivw.B1nSsJYGGlegDiRZPUQFKnKfDo7lPjIAfz8lKMdi7e	Amazon Coffee	0975059272	Phnum Penh, Phnum Penh	["American", "Mexican", "Italian", "Chinese", "Seafood", "Pasta"]	4.70	2025-06-26 02:44:19.990235	2025-06-26 02:44:19.990235	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1750905861/foodDelivery/vendors/vendor-15/b65gyyqkflyy2ky3mtwt.jpg
19	domino@pizza.com	$2y$10$8gYyXIx.XZp7nuCJkv8XE.g9MXBPG/QPx3N51YIsdfhkbELKLQ4le	Domino Pizza	0975059272	Phnum Penh, Phnum Penh	["Burgers", "Pizza"]	4.50	2025-06-26 03:20:02.991418	2025-06-26 03:20:02.991418	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1750908004/foodDelivery/vendors/vendor-19/wxowe0ulclg20rzpbbgc.png
16	starbuck@coffee.com	$2y$10$7eAT8DZOYK0DcJJfzOiiNObHtwDcMw/XlSmVqp/mDvcZXshyOG5aO	StarBuck Coffee	0123456789	Phnum Penh, Phnum Penh	["Mexican", "Japanese", "Seafood"]	4.70	2025-06-26 02:51:27.865048	2025-06-26 02:51:27.865048	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1750906290/foodDelivery/vendors/vendor-16/k5zmbfdvsmx6a4l7tvbo.png
20	coffeeconner@coffee.com	$2y$10$tOoFFIMX8YTg9ZGxfGskyuVKoWr/DwHuCDARpdM6iaeRa26GIwoaW	Coffee Conner	0975059272	Phnum Penh, Phnum Penh	["Mexican", "Seafood", "Pizza"]	4.80	2025-06-26 03:25:44.151255	2025-06-26 03:25:44.151255	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1750908346/foodDelivery/vendors/vendor-20/c6zpwy7dujygkbvlsshb.png
29	foodvillage@food.com	$2y$10$qbqDftXR3Ll/DFxxqRs70uRABSGyJOPTCuw0DlLXt58QT4v0S8X2C	Food Villege	0975059272	Phnum Penh, Phnum Penh	["Japanese", "French", "Chinese", "Mexican"]	4.90	2025-06-29 15:43:06.091459	2025-06-29 15:43:06.091459	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1751211792/foodDelivery/vendors/vendor-29/x4osps3onfryxntxwv7e.jpg
30	mhobkhmer@food.com	$2y$10$U8ZjdqhO5HOuenNusmZSjeoy4ovtmSbJ/KGuDzicrgMQ2zVnhvcsi	Mhob Khmer	0123456789	Phnum Penh, Phnum Penh	["Mediterranean", "Vegetarian", "Sushi"]	3.40	2025-06-29 15:46:29.401626	2025-06-29 15:46:29.401626	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1751211991/foodDelivery/vendors/vendor-30/xomorjkvvuozkdwnbuj7.jpg
40	burgerking@burger.com	$2y$10$9e.9BsfuHbKebcz26Ld9Ve4bz7sVPFyimcJBWc0c63kvWQSGDtMhW	Burger King	0975059272	Phnum Penh\r\nPhnum Penh	["American", "Japanese", "Mexican"]	5.00	2025-07-12 09:58:01.010813	2025-07-12 09:58:01.010813	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1752314282/foodDelivery/vendors/vendor-40/iof29kxss3apq64tooa4.png
79	Kasdfhdsm@gmail.com	$2y$10$LGA6CgMOxQlyr6wiFXnftuj2Utss/eiufRo6n2N7g4v1LH7kY16ta	Indian Palace	+15554567890	567 Maple Dr, Springfield	["geko", "gecki"]	5.00	2025-11-23 12:23:02.186717	2025-11-23 12:23:02.186717	https://res.cloudinary.com/dbs9jvz0m/image/upload/v1763900585/foodDelivery/vendors/vendor-79/y3p5wzizvyow67jxvyqw.jpg
\.


--
-- Name: admin_id_seq; Type: SEQUENCE SET; Schema: public; Owner: food_user
--

SELECT pg_catalog.setval('public.admin_id_seq', 2, true);


--
-- Name: customer_id_seq; Type: SEQUENCE SET; Schema: public; Owner: food_user
--

SELECT pg_catalog.setval('public.customer_id_seq', 45, true);


--
-- Name: food_id_seq; Type: SEQUENCE SET; Schema: public; Owner: food_user
--

SELECT pg_catalog.setval('public.food_id_seq', 34, true);


--
-- Name: food_order_id_seq; Type: SEQUENCE SET; Schema: public; Owner: food_user
--

SELECT pg_catalog.setval('public.food_order_id_seq', 115, true);


--
-- Name: inventory_id_seq; Type: SEQUENCE SET; Schema: public; Owner: food_user
--

SELECT pg_catalog.setval('public.inventory_id_seq', 32, true);


--
-- Name: order_statuses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: food_user
--

SELECT pg_catalog.setval('public.order_statuses_id_seq', 7, true);


--
-- Name: orders_id_seq; Type: SEQUENCE SET; Schema: public; Owner: food_user
--

SELECT pg_catalog.setval('public.orders_id_seq', 101, true);


--
-- Name: payment_id_seq; Type: SEQUENCE SET; Schema: public; Owner: food_user
--

SELECT pg_catalog.setval('public.payment_id_seq', 34, true);


--
-- Name: payment_method_id_seq; Type: SEQUENCE SET; Schema: public; Owner: food_user
--

SELECT pg_catalog.setval('public.payment_method_id_seq', 23, true);


--
-- Name: vendor_id_seq; Type: SEQUENCE SET; Schema: public; Owner: food_user
--

SELECT pg_catalog.setval('public.vendor_id_seq', 81, true);


--
-- Name: admin admin_email_key; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.admin
    ADD CONSTRAINT admin_email_key UNIQUE (email);


--
-- Name: admin admin_pkey; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.admin
    ADD CONSTRAINT admin_pkey PRIMARY KEY (id);


--
-- Name: customer customer_email_key; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.customer
    ADD CONSTRAINT customer_email_key UNIQUE (email);


--
-- Name: customer customer_pkey; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.customer
    ADD CONSTRAINT customer_pkey PRIMARY KEY (id);


--
-- Name: food_order food_order_pkey; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.food_order
    ADD CONSTRAINT food_order_pkey PRIMARY KEY (id);


--
-- Name: food food_pkey; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.food
    ADD CONSTRAINT food_pkey PRIMARY KEY (id);


--
-- Name: inventory inventory_pkey; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT inventory_pkey PRIMARY KEY (id);


--
-- Name: order_statuses order_statuses_key_key; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.order_statuses
    ADD CONSTRAINT order_statuses_key_key UNIQUE (key);


--
-- Name: order_statuses order_statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.order_statuses
    ADD CONSTRAINT order_statuses_pkey PRIMARY KEY (id);


--
-- Name: orders orders_pkey; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_pkey PRIMARY KEY (id);


--
-- Name: payment_method payment_method_pkey; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.payment_method
    ADD CONSTRAINT payment_method_pkey PRIMARY KEY (id);


--
-- Name: payment payment_pkey; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.payment
    ADD CONSTRAINT payment_pkey PRIMARY KEY (id);


--
-- Name: payment payment_stripe_payment_id_key; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.payment
    ADD CONSTRAINT payment_stripe_payment_id_key UNIQUE (stripe_payment_id);


--
-- Name: vendor vendor_email_key; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.vendor
    ADD CONSTRAINT vendor_email_key UNIQUE (email);


--
-- Name: vendor vendor_pkey; Type: CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.vendor
    ADD CONSTRAINT vendor_pkey PRIMARY KEY (id);


--
-- Name: idx_inventory_food; Type: INDEX; Schema: public; Owner: food_user
--

CREATE UNIQUE INDEX idx_inventory_food ON public.inventory USING btree (food_id);


--
-- Name: food_order food_order_food_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.food_order
    ADD CONSTRAINT food_order_food_id_fkey FOREIGN KEY (food_id) REFERENCES public.food(id) ON DELETE CASCADE;


--
-- Name: food food_vendor_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.food
    ADD CONSTRAINT food_vendor_id_fkey FOREIGN KEY (vendor_id) REFERENCES public.vendor(id) ON DELETE CASCADE;


--
-- Name: inventory inventory_food_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.inventory
    ADD CONSTRAINT inventory_food_id_fkey FOREIGN KEY (food_id) REFERENCES public.food(id) ON DELETE CASCADE;


--
-- Name: orders orders_customer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customer(id) ON DELETE CASCADE;


--
-- Name: orders orders_status_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.orders
    ADD CONSTRAINT orders_status_id_fkey FOREIGN KEY (status_id) REFERENCES public.order_statuses(id);


--
-- Name: payment_method payment_method_customer_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.payment_method
    ADD CONSTRAINT payment_method_customer_id_fkey FOREIGN KEY (customer_id) REFERENCES public.customer(id) ON DELETE CASCADE;


--
-- Name: payment payment_payment_method_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: food_user
--

ALTER TABLE ONLY public.payment
    ADD CONSTRAINT payment_payment_method_id_fkey FOREIGN KEY (payment_method_id) REFERENCES public.payment_method(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

