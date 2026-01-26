import mongoose from 'mongoose'
import dotenv from 'dotenv'

dotenv.config()

const connectDB = async () => {
  const mongoURI = process.env.MONGODB_URI
  if (!mongoURI) {
    throw new Error('MONGODB_URI is not defined')
  }
  await mongoose.connect(mongoURI)
}

export default connectDB

