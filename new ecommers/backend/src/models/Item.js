import mongoose from 'mongoose'

const itemSchema = new mongoose.Schema({
  name: {
    type: String,
    required: true,
    trim: true
  },
  status: {
    type: String,
    enum: ['available', 'not available'],
    default: 'available'
  },
  createdAt: {
    type: Date,
    default: Date.now
  }
})

export default mongoose.model('Item', itemSchema)

