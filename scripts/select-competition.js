import './util/optimizedResize'

export default class SelectCompetition {
  constructor(el, onChange) {
    this.el = el
    this.onChange = onChange
    this.overlay = this.getOverlay()
    this.options = el.querySelector('.options')
    this.competitions = this.options.querySelectorAll('.competition')
    this.selected = el.querySelector('.selected')

    this.selected.addEventListener('click', (evt) => this.open())
    document.addEventListener('mousedown', (evt) => this.onDocumentClick(evt))
    document.addEventListener('touchend', (evt) => this.onDocumentClick(evt))

    for (let option of this.competitions)
      option.addEventListener('click', this.onSelect)
  }
  getOverlay() {
    let overlay = document.querySelector('.skoorin-select-competitions-overlay')

    if (!overlay) {
      overlay = document.createElement('div')
      overlay.setAttribute('class', 'skoorin-select-competitions-overlay')
      document.body.insertBefore(overlay, null)
    }
    
    return overlay
  }
  onDocumentClick = (evt) => {
    let node = evt.target
    
    while (node !== null) {
      if (this.options === node || this.selected === node)
        return
      
      node = node.parentNode
    }

    this.close()
  }
  onSelect = (evt) => {
    this.close()
    this.selected.innerHTML = evt.target.innerHTML

    for (let option of this.competitions)
      option === evt.target
        ? option.classList.add('active')
        : option.classList.remove('active')

    if (typeof this.onChange === 'function')
      this.onChange(evt.target.dataset.id)
  }
  open = () => {
    if (this.isVisible(this.options))
      return this.close()

    this.options.style.display = 'block'
    this.position(true)
    optimizedResize.add(this.position)
  }
  close = () => {
    this.options.style.display = 'none'
    this.overlay.style.display = 'none'
    optimizedResize.remove(this.position)
  }
  position = (opening = false) => {
    if (window.innerWidth <= 640) {
      this.overlay.style.display = 'block'
      this.options.style.position = 'fixed'
      this.options.style.left = 'auto'
      this.options.style.top = 'auto'
      this.options.style.left = (Math.ceil(window.innerWidth) - this.options.offsetWidth) / 2 + 'px'
      this.options.style.top = (Math.ceil(window.innerHeight) - this.options.offsetHeight) / 2 + 'px'
    } else {
      this.overlay.style.display = 'none'
      this.options.style.position = 'absolute'
      this.options.style.left = ''
      this.options.style.top = ''
    }
    this.prevWinWidth = window.innerWidth
  }
  isVisible = (el) =>
    !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length)
}
