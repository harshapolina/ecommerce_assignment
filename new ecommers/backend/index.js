import dotenv from 'dotenv'
dotenv.config()

import express from 'express'
import cors from 'cors'
import connectDB from './src/config/db.js'
import userRoutes from './src/routes/users.js'
import itemRoutes from './src/routes/items.js'
import cartRoutes from './src/routes/carts.js'
import orderRoutes from './src/routes/orders.js'

const app = express()

app.use(cors())
app.use(express.json())

app.use('/users', userRoutes)
app.use('/items', itemRoutes)
app.use('/carts', cartRoutes)
app.use('/orders', orderRoutes)

app.get('/', (req, res) => {
  res.json({ message: 'Shopping Cart API' })
})

connectDB()
  .then(() => {
    const PORT = process.env.PORT || 3001
    app.listen(PORT, () => {
      console.log('Server running on port', PORT)
    })
  })
  .catch((e) => {
    console.error('Database connection failed:', e)
  })

