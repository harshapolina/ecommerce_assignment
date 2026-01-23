import mongoose from 'mongoose'

const cartSchema = new mongoose.Schema({
  userId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'User',
    required: true
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

cartSchema.index({ userId: 1, status: 1 }, { unique: true, partialFilterExpression: { status: 'active' } })

export default mongoose.model('Cart', cartSchema)

