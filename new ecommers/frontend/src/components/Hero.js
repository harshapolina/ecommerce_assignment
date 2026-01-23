import './Hero.css'

function Hero() {
  const handleShopNow = () => {
    const productsSection = document.querySelector('.products-section')
    if (productsSection) {
      productsSection.scrollIntoView({ behavior: 'smooth' })
    }
  }

  return (
    <section className="hero">
      <div className="hero-container">
        <div className="hero-content">
          <h1 className="hero-title">Bring The Nature Close To You</h1>
          <p className="hero-subtitle">Discover premium products and transform your shopping experience</p>
          <button className="hero-button" onClick={handleShopNow}>Shop Now</button>
        </div>
        <div className="hero-image">
          <img 
            src="https://images.unsplash.com/photo-1463154545680-d59320fd685d?w=600&h=600&fit=crop" 
            alt="Plants" 
            className="hero-plant-image"
          />
        </div>
      </div>
    </section>
  )
}

export default Hero

