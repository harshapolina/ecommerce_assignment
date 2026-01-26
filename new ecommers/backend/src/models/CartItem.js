import mongoose from 'mongoose'

const cartItemSchema = new mongoose.Schema({
  cartId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Cart',
    required: true
  },
  itemId: {
    type: mongoose.Schema.Types.ObjectId,
    ref: 'Item',
    required: true
  },
  quantity: {
    type: Number,
    default: 1
  }
})

cartItemSchema.index({ cartId: 1, itemId: 1 }, { unique: true })

export default mongoose.model('CartItem', cartItemSchema)

