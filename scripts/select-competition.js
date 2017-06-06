import './util/optimizedResize'

export default class SelectCompetition {
  constructor(el, onChange) {
    this.el = el
    this.onChange = onChange
    this.overlay = this.getOverlay()
    this.options = el.querySelector('.options')
    this.competitions = this.options.querySelectorAll('.competition')
    this.selectedOption = el.querySelector('.selected option')

    el.querySelector('.selected').addEventListener('click', (evt) => this.open())
    document.addEventListener('mousedown', (evt) => this.onDocumentClick(evt))
    document.addEventListener('touchend', (evt) => this.onDocumentClick(evt))

    this.activeCompetitionIdx = 0
    for (let i = 0; i < this.competitions.length; i++) {
      this.competitions[i].addEventListener('click', this.onSelect)
      if (this.competitions[i].classList.contains('active'))
        this.activeCompetitionIdx = i
    }

    const buttons = el.querySelectorAll('.competition-nav-button')
    this.prevBtn = buttons[0]
    this.nextBtn = buttons[1]
    this.setButtonStates()
    this.prevBtn.addEventListener('click', () => {
      clearTimeout(this.btnThrottle)
      this.btnThrottle = setTimeout(this.onClickPrev, 400)
    })
    this.nextBtn.addEventListener('click', () => {
      clearTimeout(this.btnThrottle)
      this.btnThrottle = setTimeout(this.onClickNext, 400)
    })
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
    this.selectCompetition(evt.target)
  }
  selectCompetition = (el) => {
    this.selectedOption.textContent = el.innerHTML

    for (let i = 0; i < this.competitions.length; i++)
      if (this.competitions[i] === el) {
        this.activeCompetitionIdx = i
        this.competitions[i].classList.add('active')
      } else
        this.competitions[i].classList.remove('active')

    this.setButtonStates()
    
    if (typeof this.onChange === 'function')
      this.onChange(el.dataset.id)
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
  onClickPrev = () => {
    this.selectCompetition(
      this.competitions[this.activeCompetitionIdx - 1]
    )
  }
  onClickNext = () => {
    this.selectCompetition(
      this.competitions[this.activeCompetitionIdx + 1]
    )
  }
  setButtonStates() {
    this.activeCompetitionIdx == 0
      ? this.prevBtn.classList.add('disabled')
      : this.prevBtn.classList.remove('disabled')
    this.activeCompetitionIdx == this.competitions.length-1
      ? this.nextBtn.classList.add('disabled')
      : this.nextBtn.classList.remove('disabled')
  }
}
