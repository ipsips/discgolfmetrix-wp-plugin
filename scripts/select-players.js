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
    this.setState(state)
  }
  setState(state) {
    this.state = Object.assign({}, this.state, state)
    this.render()
  }
  render() {
    const { selected, lastChanged } = this.state.filters.players
    const isAnySelected = Array.isArray(selected) && selected.length
    const options = this.state.data.filters.players.slice(0)

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
        className="skoorin-results-filter-control-select-players"
        on-click={open}
        on-touchstart={open}
        >
        <select class={{ placeholder:1, visible: !this.state.showMultiselect }}>
          <option>
            {!isAnySelected
              ? window.skoorinResults.l10n.all.players
              : selected.length === 1
                ? selected[0]
                : window.skoorinResults.l10n.multiple.players
            }
          </option>
        </select>
        <select
          key="select-players"
          name="players"
          multiple
          autoComplete="off"
          on-change={this.onChange}
          class={{ visible: this.state.showMultiselect }}
          >
          <option key="all" value="all" selected={!isAnySelected}>
            {window.skoorinResults.l10n.all.players}
          </option>
          {options.map(({ Name }) =>
            <option
              key={`${Name}-${lastChanged || Date.now()}`}
              value={Name}
              selected={isAnySelected && selected.indexOf(Name) > -1}
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
    }
  }
  close() {
    if (this.state.showMultiselect)
      this.setState({ showMultiselect: false })
  }
  onChange = (evt) => {
    this.store.dispatch({
      type: 'FILTER',
      payload: {
        players: {
          selected: getMultiSelectValue(evt.target),
          lastChanged: Date.now() // to force-patch vNode
        }
      }
    })
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
}
