/** @jsx html */

import './util/optimizedResize'
import { html } from 'snabbdom-jsx'
import { patch } from './util/snabbdom'
import { createStore, applyMiddleware } from 'redux'
import api from './util/redux-middleware/api'

const reducers = {
  loading(state, action) {
    switch (action.type) {
      case 'FETCH_RESULTS_REQ':
        return true
      case 'FETCH_RESULTS_RES':
      case 'FETCH_RESULTS_ERR':
        return false
      default:
        return state
    }
  },
  data(state, action) {
    switch (action.type) {
      case 'FETCH_RESULTS_RES':
        return {
          ...state,
          results: action.response
        }
      case 'FETCH_RESULTS_ERR':
        console.error('FETCH_RESULTS_ERR', action.error)
      default:
        return state
    }
  },
  filters(state, action) {
    if (action.type != 'FILTER')
      return state

    const newState = {}

    /* reset all filters */
    for (let name in state)
      newState[name] = 'all'

    return {
      ...newState,
      ...action.payload
    }
  }
}

class SkoorinResults {
  constructor(results) {
    this.results = results
    this.table = results.querySelector('.skoorin-results-table')
    this.store = createStore(
      (state, action) => ({
        loading: reducers.loading(state.loading, action),
        data: reducers.data(state.data, action),
        filters: reducers.filters(state.filters, action)
      }),
      this.getInitialState(),
      applyMiddleware(api)
    )

    this.store.subscribe(this.onStateChange)
    this.onStateChange()

    new SkoorinCompetitionSelect(
      results.querySelector('.skoorin-select-competition'),
      this.onCompetitionSelect
    )
  }
  getInitialState = () => {
    const data = JSON.parse(this.results.querySelector('.skoorin-results-data').innerHTML)

    return {
      loading: false,
      data,
      filters: this.initFilters(data.filters_selected, data.filters)
    }
  }
  initFilters = (filtersSelected, filters) => {
    const state = {}
    this.filters = {}

    filtersSelected.forEach(name => {
      if (name != 'competition') {
        const filter = this.results.querySelector(`select[name="${name}"]`)

        state[name] = filter.value
        this.filters[name] = filter
        
        filter.addEventListener('change', (evt) => {
          this.store.dispatch({
            type: 'FILTER',
            payload: {
              [name]: evt.target.value
            }
          })
        })
      }
    })

    return state
  }
  onStateChange = () => {
    this.state = this.store.getState()

    this.renderTable()

    for (let name in this.filters)
      this.renderFilter(name)
  }
  renderFilter = (name) => {
    const allProps = this.state.filters[name] == 'all'
      ? { selected: true }
      : {}
    const filter = <select name={name}>
      <option value="all" { ...allProps }>{window.skoorinResults.l10n.all[name]}</option>
      {this.state.data.filters[name].map(option =>
        <option value={option.id}>{option.name}</option>
      )}
    </select>

    /* clear SSR table before patching in a vnode */
    if (this.filters[name] instanceof Element)
      this.filters[name].innerHTML = ''

    this.filters[name] = patch(this.filters[name], filter)
  }
  renderTable = () => {
    const table = <div className="skoorin-results-table" class-loading={this.state.loading}>
      <div className="skoorin-results-table-container table-scroll table-responsive">
        <table>
          <colgroup>
            <col width="0%"/>
            <col width="100%"/>
          </colgroup>
          <thead>
            <tr>
              <th className="hole" colSpan="2">{window.skoorinResults.l10n.hole}</th>
              {this.state.data.results.holes.map((par, idx) =>
                <th>{idx + 1}</th>
              )}
              <th>{window.skoorinResults.l10n.tot}</th>
              <th>{window.skoorinResults.l10n.to_par}</th>
            </tr>
            <tr className="par">
              <th className="par" colSpan="2">{window.skoorinResults.l10n.par}</th>
              {this.state.data.results.holes.map((par, idx) =>
                <th>{par}</th>
              )}
              <th>{this.state.data.results.par_total}</th>
              <th></th>
            </tr>
          </thead>
          {this.filterPlayers().map((player, idx) =>
            <tbody>
              <tr key={player.id}>
                <td className="standing">{idx + 1}</td>
                <td className="player">
                  <a on-click={this.toggleResultsExtra} href={`https://skoorin.com/?u=player_stat&player_user_id=${player.id}`} target="_blank">
                    <i/> {player.name}
                  </a>
                </td>
                {player.results.throws.map((score, idx) =>
                  <td className={this.getScoreClass(idx, player)}>{score}</td>
                )}
                <td className="total">{player.results.total}</td>
                <td className="balance">{(player.results.to_par >= 0 ? '+' : '')+player.results.to_par}</td>
              </tr>
              {player.results.hasOwnProperty('extra') && player.results.extra instanceof Array &&
                player.results.extra.map(extra =>
                  <tr className={`extra ${extra.name}`}>
                    <td colSpan="2">{window.skoorinResults.l10n.extra[extra.name]}</td>
                    {extra.holes.map(val =>
                      <td>{this.getExtraNotation(val, extra.type)}</td>
                    )}
                    <td colSpan="2">{extra.total}</td>
                  </tr>
                )
              }
            </tbody>
          )}
        </table>
      </div>
      <span className="spinner"><i/><i/></span>
    </div>

    /* clear SSR table before patching in a vnode */
    if (this.table instanceof Element)
      this.table.innerHTML = ''

    this.table = patch(this.table, table)
  }
  filterPlayers = () => {
    /* if any of the filters is not set to "all" */
    for (let name in this.state.filters)
      if (this.state.filters[name] != 'all')
        return this.state.data.results.players.filter(player =>
          name == 'player'
            ? this.state.filters[name] == player.id
            : player.hasOwnProperty(name) && this.state.filters[name] == player[name]
        )

    /* else all filters are set to "all" */
    return this.state.data.results.players
  }
  getScoreClass = (idx, player) => {
    const throws = player.results.throws[idx]
    const par = this.state.data.results.holes[idx]
    const ob = player.results.hasOwnProperty('ob') && player.results.ob[idx] ? ' ob' : ''

    if (throws == 1)
      return 'hole-in-one'

    switch (throws - par) {
      case -2:
        return 'eagle'+ob
      case -1:
        return 'birdie'+ob
      case 0:
        return 'par'+ob
      case 1:
        return 'bogey'+ob
      case 2:
        return 'double-bogey'+ob
      default:
        return 'fail'+ob
    }
  }
  getExtraNotation = (value, type) => {
    switch (type) {
      case 'bool':
        return value ? '✔' : ''
      case 'number':
      default:
        return value
    }
  }
  onCompetitionSelect = (competitionId) => {
    this.store.dispatch({
      type: 'API_REQ',
      payload: {
        types: ['FETCH_RESULTS_REQ', 'FETCH_RESULTS_RES', 'FETCH_RESULTS_ERR'],
        query: `content=result_json&id=${competitionId}`
      }
    })
  }
  toggleResultsExtra = (evt) => {
    evt.preventDefault()

    let tr = evt.target
    
    while (tr.tagName !== 'TR')
      tr = tr.parentNode

    tr.classList.contains('expanded')
      ? tr.classList.remove('expanded')
      : tr.classList.add('expanded')
  }
}

class SkoorinCompetitionSelect {
  constructor(select, onChange) {
    this.select = select
    this.onChange = onChange
    this.overlay = this.getOverlay()
    this.options = select.querySelector('.options')
    this.competitions = this.options.querySelectorAll('.competition')
    this.selected = select.querySelector('.selected')

    this.selected.addEventListener('click', (evt) => this.open())
    document.addEventListener('mousedown', (evt) => this.onDocumentClick(evt))
    document.addEventListener('touchend', (evt) => this.onDocumentClick(evt))

    for (let option of this.competitions)
      option.addEventListener('click', this.onSelect)
  }
  getOverlay = () => {
    let overlay = document.querySelector('.skoorin-select-competition-overlay')

    if (!overlay) {
      overlay = document.createElement('div')
      overlay.setAttribute('class', 'skoorin-select-competition-overlay')
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
    if (window.innerWidth <= 640/* && (opening || this.prevWinWidth > 640)*/) {
      this.overlay.style.display = 'block'
      this.options.style.position = 'fixed'
      this.options.style.left = 'auto'
      this.options.style.top = 'auto'
      this.options.style.left = (Math.ceil(window.innerWidth) - this.options.offsetWidth) / 2 + 'px'
      this.options.style.top = (Math.ceil(window.innerHeight) - this.options.offsetHeight) / 2 + 'px'
    }
    else/* if (window.innerWidth > 640 && (opening || this.prevWinWidth <= 640))*/ {
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

for (let results of document.querySelectorAll('.skoorin-results'))
  new SkoorinResults(results)