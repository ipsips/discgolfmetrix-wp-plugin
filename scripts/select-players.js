/** @jsx html */
import { html } from 'snabbdom-jsx'
import { patch } from './util/snabbdom'
import { getDeepProp, getMultiSelectValue } from './util'

export default class SelectPlayers {
  constructor({ container, select }, state, store) {
    this.container = container
    this.select = select
    this.store = store
    document.addEventListener('mousedown', (evt) => this.onDocumentClick(evt))
    document.addEventListener('touchend', (evt) => this.onDocumentClick(evt))
    document.addEventListener('touchstart', (evt) => this.setState({ touch: true }))
    this.setState(state)
  }
  setState(state) {
    this.state = Object.assign({}, this.state, state)
    this.render()
  }
  render() {
    const { selected, lastChanged } = this.state.filters.players
    const isAllSelected = this.isAllSelected()
    const options = (getDeepProp(this.state, 'data.filters.players') || []).slice(0)

    // sort alphabetically
    options.sort((a, b) =>
      a.Name.toLowerCase() > b.Name.toLowerCase()
        ? 1
        : a.Name.toLowerCase() < b.Name.toLowerCase()
          ? -1
          : 0
    )

    const open = this.open.bind(this)
    const container = (
      <div
        key="container"
        class={{
          'discgolfmetrix-results-filter-control-select-players': 1,
          'no-options': !options.length,
          touch: this.state.touch
        }}
        on-click={open}
        on-touchstart={open}
        >
        <select class={{ placeholder:1, visible: !this.state.showMultiselect }}>
          <option>
            {isAllSelected
              ? window.discgolfmetrixResults.l10n.all.players
              : selected.length === 1
                ? selected[0]
                : window.discgolfmetrixResults.l10n.multiple.players
            }
          </option>
        </select>
        <select
          key="select-players"
          name="players"
          multiple
          autoComplete="off"
          on-change={this.onChange}
          on-blur={this.onBlur}
          class={{ visible: this.state.showMultiselect }}
          >
          <option key="all" value="all" selected={isAllSelected}>
            {window.discgolfmetrixResults.l10n.all.players}
          </option>
          {options.map(({ Name }) =>
            <option
              key={`${Name}-${lastChanged || Date.now()}`}
              value={Name}
              selected={selected.indexOf(Name) > -1}
              >
              {Name}
            </option>
          )}
        </select>
      </div>
    )

    /* clear SSR element before patching in a vnode */
    if (this.container instanceof Element)
      this.container.innerHTML = ''

    this.container = patch(this.container, container)
  }
  open() {
    if (!this.state.showMultiselect) {
      const newState = {
        showMultiselect: true,
        hasOpened: true
      }

      if (!this.state.hasOpened)
        newState.filters = {
          ...this.state.filters,
          players: {
            ...this.state.filters.players,
            lastChanged: Date.now()
          }
        }

      this.setState(newState)
      
      // IE workaround
      document.querySelector('.discgolfmetrix-results-filter select.placeholder').blur()
    }
  }
  close() {
    if (this.state.showMultiselect)
      this.setState({ showMultiselect: false })
  }
  onChange = (evt) => {
    this.dispatchAction(
      getMultiSelectValue(evt.target)
    )
  }
  onBlur = () => {
    if (this.isAllSelected())
      this.dispatchAction(['all'])
    
    this.close()
  }
  isAllSelected = () => {
    const { selected } = this.state.filters.players

    return !Array.isArray(selected)
      || !selected.length
      || selected.indexOf('all') > -1
  }
  onDocumentClick = (evt) => {
    let node = evt.target
    
    while (node !== null) {
      if (this.container.elm === node)
        return
      
      node = node.parentNode
    }

    this.close()
  }
  dispatchAction(selected) {
    this.store.dispatch({
      type: 'FILTER',
      payload: {
        players: {
          selected,
          lastChanged: Date.now() // to force-patch vNode
        }
      }
    })
  }
}
