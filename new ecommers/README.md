# Shopping Cart Application

A simple shopping cart app where users can browse items, add them to cart, and place orders.

## What This Does

Users can:
- Register and login
- View all available items
- Add items to cart
- Remove items from cart
- View cart
- Place orders
- See order history

## Tech Stack

**Backend:**
- Node.js with Express
- MongoDB with Mongoose
- JWT for authentication

**Frontend:**
- React
- React Router for navigation
- Sonner for toast notifications

## Setup

### Backend

1. Go to backend folder:
bash
cd backend


2. Install packages:
  bash
npm install


3. Create a `.env` file and add:

MONGODB_URI=your_mongodb_connection_string
JWT_SECRET=your_secret_key
PORT=3001


4. Start the server:
bash
npm run dev


The backend will run on `http://localhost:3001`

### Frontend

1. Go to frontend folder:
bash
cd frontend


2. Install packages:
bash
npm install


3. Start the app:
bash
npm run dev


The frontend will run on `http://localhost:5173`

## API Endpoints

### Users
- `POST /users` - Register new user
- `POST /users/login` - Login
- `POST /users/logout` - Logout
- `GET /users` - Get all users (admin)

### Items
- `GET /items` - Get all items
- `POST /items` - Create item (admin)
- `PUT /items/:id` - Update item (admin)
- `DELETE /items/:id` - Delete item (admin)

### Cart
- `POST /carts` - Add item to cart
- `GET /carts/my-cart` - Get my cart
- `DELETE /carts/:itemId` - Remove item from cart

### Orders
- `POST /orders` - Create order (checkout)
- `GET /orders` - Get my orders

## Features

- Single device login (one user can only be logged in on one device at a time)
- One cart per user
- Items are automatically categorized (Indoor Plants, Outdoor Plants, Herbal Plants)
- Toast notifications for user actions
- Order history with item details

## Project Structure


backend/
  src/
    controllers/    # Request handlers
    models/         # Database models
    routes/         # API routes
    middleware/     # Auth middleware
    config/        # Database config

frontend/
  src/
    components/     # React components
    utils/          # API functions


## Notes

- Make sure MongoDB is running
- Backend uses port 3001 by default
- Frontend uses port 5173 by default
- All API calls need authentication except login and register

