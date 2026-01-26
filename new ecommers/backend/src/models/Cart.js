import mongoose from 'mongoose'

const cartSchema = new mongoose.Schema({
  userId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
  },
  name: {
    type: String,
    default: ''
  },
  status: {
    type: String,
    default: 'active'
  },
  createdAt: {
    type: Date,
    default: Date.now
  }
})

cartSchema.index({ userId: 1 }, { unique: true })

export default mongoose.model('Cart', cartSchema)

