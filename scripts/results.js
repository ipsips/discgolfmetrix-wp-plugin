/** @jsx html */
import './util/optimizedResize'
import { html } from 'snabbdom-jsx'
import { patch } from './util/snabbdom'
import { createStore, applyMiddleware } from 'redux'
import api from './util/redux-middleware/api'
import SelectCompetition from './select-competition'
import SelectPlayers from './select-players'
import ResultsTable from './results-table'
import { getDeepProp, getMultiSelectValue } from './util'

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
        const gotCompetitionData = !!getDeepProp(action, 'response.Competition')

        if (!gotCompetitionData)
          alert(window.skoorinResults.l10n.missing_data_error)

        return !gotCompetitionData
          ? state
          : {
            ...state,
            results: action.response
          }
      case 'FETCH_FILTER_RES':
        const gotFilterData = !!getDeepProp(action, 'response.competitions')

        if (!gotFilterData)
          alert(window.skoorinResults.l10n.missing_data_error)

        return !gotFilterData
          ? state
          : {
            ...state,
            filters: action.response
          }
      case 'FETCH_RESULTS_ERR':
      case 'FETCH_FILTER_ERR':
        alert(window.skoorinResults.l10n.network_error)
      default:
        return state
    }
  },
  filters(state, action) {
    if (action.type != 'FILTER')
      return state

    const newState = {}

    // Reset all filters (two filters cannot be active at the same time)
    for (let name in state)
      newState[name] = name == 'players'
        ? { selected: 'all', lastChanged: Date.now() }
        : 'all'

    return {
      ...newState,
      ...action.payload
    }
  }
}

class Results {
  constructor(el, state) {
    this.el = el
    this.store = createStore(
      (state, action) => ({
        loading: reducers.loading(state.loading, action),
        data: reducers.data(state.data, action),
        filters: reducers.filters(state.filters, action)
      }),
      this.getInitialState(state),
      applyMiddleware(api)
    )

    this.store.subscribe(() =>
      this.setState(this.store.getState())
    )
    this.setState(this.store.getState())
  }
  getInitialState(state) {
    return {
      loading: false,
      data: state,
      filters: this.initFilters(state.filters_selected, state.filters)
    }
  }
  setState(state) {
    this.state = Object.assign({}, this.state, state)
    this.render()
  }
  initFilters(filtersSelected, filters) {
    const state = {}
    this.filters = {}

    filtersSelected.forEach(name => {
      if (name != 'competitions') {
        const container = this.el.querySelector(`.skoorin-results-filter-control-select-${name}`)
        const select = container.querySelector(`select[name="${name}"]`)

        this.filters[name] = { container, select }
        
        if (name == 'players')
          state[name] = {
            selected: getMultiSelectValue(select),
            lastChanged: Date.now()
          }
        else
          state[name] = select.value
      }
    })

    return state
  }
  render() {
    if (!this.table)
      this.table = new ResultsTable(
        this.el.querySelector('.skoorin-results-table'),
        this.state
      )
    else
      this.table.setState(this.state)

    if (!this.competitionSelect)
      this.competitionSelect = new SelectCompetition(
        this.el.querySelector('.skoorin-select-competitions'),
        this.onCompetitionSelect
      )

    for (let name in this.filters)
      if (name == 'players')
        this.playersFilter
          ? this.playersFilter.setState(this.state)
          : (
            this.playersFilter = new SelectPlayers(
              this.filters[name],
              this.state,
              this.store
            )
          )
      else
        this.renderFilter(name)
  }
  renderFilter(name) {
    const selected = this.state.filters[name]
    const options = getDeepProp(this.state, ['data', 'filters', name])
    const container = <div class={{
      [`skoorin-results-filter-control-select-${name}`]: 1,
      'no-options': !Array.isArray(options) || !options.length
      }}>
      <select name={name} on-change={this.onChangeFilter.bind(this, name)}>
        <option value="all" selected={selected == 'all'}>
          {window.skoorinResults.l10n.all[name]}
        </option>
        {!Array.isArray(options) ? '' : options.map(option => {
          const value = name == 'groups'
            ? option.Number
            : option.Name
          return <option value={value} selected={selected == value}>
            {value}
          </option>
        })}
      </select>
    </div>

    /* clear SSR select before patching in a vnode */
    if (this.filters[name].container instanceof Element)
      this.filters[name].container.innerHTML = ''

    this.filters[name].container = patch(this.filters[name].container, container)
  }
  onCompetitionSelect = (competitionId) => {
    // clear filters
    this.store.dispatch({
      type: 'FILTER',
      payload: {}
    })
    // get results data
    this.store.dispatch({
      type: 'API_REQ',
      payload: {
        types: ['FETCH_RESULTS_REQ', 'FETCH_RESULTS_RES', 'FETCH_RESULTS_ERR'],
        query: `content=result&id=${competitionId}`
      }
    })
    // get filters data
    this.store.dispatch({
      type: 'API_REQ',
      payload: {
        types: ['FETCH_FILTER_REQ', 'FETCH_FILTER_RES', 'FETCH_FILTER_ERR'],
        query: `content=wordpress_filters&competition_id=${competitionId}`
      }
    })
  }
  onChangeFilter(name, evt) {
    this.store.dispatch({
      type: 'FILTER',
      payload: {
        [name]: evt.target.value
      }
    })
  }
}

for (let results of document.querySelectorAll('.skoorin-results')) {
  let resultsData
  try {
    resultsData = JSON.parse(results.querySelector('.skoorin-results-data').innerHTML)
  } catch(err) {
    console.error('Could not parse results data. This could mean there was a network error when getting the data from Skoorin data API.\n', err)
    break
  }
  new Results(results, resultsData)
}
