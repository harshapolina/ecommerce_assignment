import mongoose from 'mongoose'
import dotenv from 'dotenv'

dotenv.config()

const connectDB = async () => {
  await mongoose.connect(process.env.MONGODB_URI || 'mongodb+srv://harshapolina1_db_user:hvs2006@project-1.jscyy6f.mongodb.net/shopping_cart?appName=Project-1')
}

export default connectDB

