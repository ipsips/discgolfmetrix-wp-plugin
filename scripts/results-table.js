/** @jsx html */
import { html } from 'snabbdom-jsx'
import h from 'snabbdom/h'
import { patch } from './util/snabbdom'
import { deepCopy, getDeepProp, arraySum } from './util'

const profileIcon = `<svg width="100%" height="100%" viewBox="0 0 512 512" preserveAspectRatio="xMidYMid meet"><path d="${window.skoorinResults.profile_link_icon_path}"/></svg>`
const noClassFlag = '$___NO_CLASS'
const extras = {
  BUE: { type: Boolean, totalFmt: 'percent' },
  GRH: { type: Boolean, totalFmt: 'percent' },
  OCP: { type: Boolean, totalFmt: 'sum' },
  ICP: { type: Number, totalFmt: 'percent' },
  IBP: { type: Boolean, totalFmt: 'sum' },
  PEN: { type: Number, totalFmt: 'sum' }
}
const addPlus = (value) => (parseFloat(value) > 0 ? '+' : '')+value

export default class ResultsTable {
  constructor(el, state) {
    this.el = el
    this.competitionID = null
    this.setState(state)
  }
  setState(state) {
    this.state = state
    this.render()
  }
  render() {
    const { Competition } = getDeepProp(this.state, 'data.results')
    const hasExtras = Competition.hasOwnProperty('MetrixMode') && Competition.MetrixMode == 2
    const hasSubcompetitions = !!(Competition.SubCompetitions || []).length
    const showPreviousRoundsSum = Competition.hasOwnProperty('ShowPreviousRoundsSum') && !!parseInt(Competition.ShowPreviousRoundsSum, 10)
    if (this.competitionID != Competition.ID) {
      this.playersByClasses = this.getPlayersByClasses(this.aggregateResults())
      this.orderPlayersByTotalScore()
      this.rankPlayers()
      this.competitionID = Competition.ID
    }
    let parTotal = 0, colSpan = hasSubcompetitions ? 3 : 2
    const playersByClasses = this.filterPlayers()
    const table = (
      <div className="skoorin-results-table" class-loading={this.state.loading}>
        <div className="skoorin-results-table-container table-scroll table-responsive">
          <table>
            <colgroup>
              <col width="0%"/>
              <col width="100%"/>
            </colgroup>
            <thead>
              <tr>
                <th className="hole" colSpan={colSpan}>{window.skoorinResults.l10n.hole}</th>
                {this.state.data.results.Competition.Tracks.map(({ Number }, idx) =>
                  <th>{Number}</th>
                )}
                <th>{window.skoorinResults.l10n.tot}</th>
                <th>{window.skoorinResults.l10n.to_par}</th>
                {hasSubcompetitions || showPreviousRoundsSum ? [<th/>, <th/>] : ''}
              </tr>
              <tr className="par">
                <th className="par" colSpan={colSpan}>{window.skoorinResults.l10n.par}</th>
                {this.state.data.results.Competition.Tracks.map(({ Par }, idx) => {
                  parTotal += parseInt(Par, 10)
                  return <th>{Par}</th>
                })}
                <th>{parTotal}</th>
                <th></th>
                {hasSubcompetitions || showPreviousRoundsSum ? [<th/>, <th/>] : ''}
              </tr>
            </thead>
            {playersByClasses.order.map((className) =>
              [className == noClassFlag || !playersByClasses.byName[className].order.length ? '' :
                <thead key={Competition.ID+'/'+className}>
                  <th className="class" colSpan={Competition.Tracks.length + (hasSubcompetitions ? 7 : showPreviousRoundsSum ? 6 : 4)}>{className}</th>
                </thead>,
                playersByClasses.byName[className].order.map((playerName, idx) => {
                  const player = playersByClasses.byName[className].byName[playerName]
                  const competitionKeys = Object.keys(player.PlayerResults)
                  const rowSpan = hasSubcompetitions && hasExtras
                    ? competitionKeys.length + (competitionKeys.length * Object.keys(extras).length)
                    : competitionKeys.length
                  const totalSum = arraySum(Object.values(player.Sum))
                  const totalDiff = arraySum(Object.values(player.Diff))
                  const key = Competition.ID+'/'+(player.UserID || player.Name)
                  
                  return <tbody key={key}>
                    {competitionKeys.map((competitionKey, idx) => {
                      const scoresRow = (
                        <tr key={key+'/'+competitionKey}>
                          {idx !== 0 ? '' : [
                            <td key={[key, competitionKey, 'standing'].join('/')} className="standing" rowSpan={rowSpan}>{player.standing}</td>,
                            <td key={[key, competitionKey, 'player'].join('/')} className="player" rowSpan={rowSpan} title={player.Name}>
                              {hasExtras
                                ? <a className="expand-metrix" on-click={this.toggleResultsExtra} href="#">
                                    <i/> {player.Name}
                                  </a>
                                : player.Name
                              }
                              {!player.hasOwnProperty('UserID') ? '' :
                                <a
                                  className="profile-link"
                                  target="_blank"
                                  href={`https://dgmtrx.com/?u=player_stat&player_user_id=${player.UserID}`}>
                                  {h('span', { props: { innerHTML: profileIcon }})}
                                </a>
                              }
                            </td>
                          ]}
                          {!hasSubcompetitions ? '' :
                            this.getSubcompetitionCell(competitionKey)
                          }
                          {player.PlayerResults[competitionKey].map((score, idx) =>
                            this.getScoreCell(player, competitionKey, score, idx)
                          )}
                          <td className="sum">{player.Sum[competitionKey]}</td>
                          <td className="diff">{addPlus(player.Diff[competitionKey])}</td>
                          {hasSubcompetitions && idx === 0 ? [
                            <td key={[key, competitionKey, 'sum'].join('/')} className="sum" rowSpan={rowSpan}>{totalSum}</td>,
                            <td key={[key, competitionKey, 'diff'].join('/')} className="diff" rowSpan={rowSpan}>{addPlus(totalDiff)}</td>
                          ] : !showPreviousRoundsSum ? '' : [
                            <td key={[key, competitionKey, 'sum'].join('/')} className="sum" rowSpan={rowSpan}>{player.PreviousRoundsSum}</td>,
                            <td key={[key, competitionKey, 'diff'].join('/')} className="diff" rowSpan={rowSpan}>{addPlus(player.PreviousRoundsDiff)}</td>
                          ]}
                        </tr>
                      )

                      return hasExtras
                        ? [scoresRow].concat(this.getExtraRows(player, competitionKey, hasSubcompetitions, showPreviousRoundsSum))
                        : scoresRow
                    })}
                  </tbody>
                })
              ]
            )}
          </table>
        </div>
        <span className="spinner"><i/><i/></span>
      </div>
    )

    // clear SSR table before patching in a vnode
    if (this.el instanceof Element)
      this.el.innerHTML = ''

    this.el = patch(this.el, table)
  }
  /**
   * Aggregates results and subcompetition results
   */
  aggregateResults() {
    const players = { byName: {}, order: [] }
    const add = (competition) => {
      const competitionKey = competition.Date+'T'+competition.Time

      ;(competition.Results || []).forEach(player => {
        if (!players.byName.hasOwnProperty(player.Name)) {
          players.byName[player.Name] = Object.assign({}, player)
          players.byName[player.Name].Sum = {}
          players.byName[player.Name].Diff = {}
          players.byName[player.Name].PlayerResults = { [competitionKey]: [] }
          players.order.push(player.Name)
        }
        players.byName[player.Name].Sum[competitionKey] = player.Sum
        players.byName[player.Name].Diff[competitionKey] = player.Diff
        players.byName[player.Name].PlayerResults[competitionKey] = []

        ;(player.PlayerResults || []).forEach(score =>
          players.byName[player.Name].PlayerResults[competitionKey].push(score)
        )
      })

      if (competition.SubCompetitions)
        if ((competition.SubCompetitions || []).length)
          competition.SubCompetitions.forEach(add)
    }

    const { Competition } = getDeepProp(this.state, 'data.results')

    if (Competition)
      add(Competition)

    return players
  }
  getPlayersByClasses(players) {
    return players.order.reduce((classes, playerName) => {
      const className = players.byName[playerName].ClassName || noClassFlag

      if (!classes.byName.hasOwnProperty(className)) {
        classes.byName[className] = { byName: {}, order: [] }
        classes.order.push(className)
      }

      classes.byName[className].byName[playerName] = players.byName[playerName]
      classes.byName[className].order.push(playerName)

      return classes
    }, {
      byName: {}, order: []
    })
  }
  /**
   * Orders players by total score. Mutates this.playersByClasses.
   */
  orderPlayersByTotalScore() {
    const { Competition } = getDeepProp(this.state, 'data.results')
    
    if (!Competition || !this.playersByClasses)
      return
    
    const numCompetitions = (
      Array.isArray(Competition.SubCompetitions)
        ? Competition.SubCompetitions.length
        : 0
    ) + (
      Array.isArray(Competition.Results) && Competition.Results.length
        ? 1
        : 0
    )
    const missingCompetitions = (player) =>
      ({}).toString.call(player.PlayerResults) != '[object Object]' ||
      Object.keys(player.PlayerResults).length < numCompetitions
    const missingResults = (player) => {
      for (let competitionKey in player.PlayerResults)
        for (let score of player.PlayerResults[competitionKey])
          if (({}).toString.call(score) != '[object Object]' || !score.hasOwnProperty('Result'))
            return true
      return false
    }
    const prop = Competition.hasOwnProperty('ShowPreviousRoundsSum') && !!parseInt(Competition.ShowPreviousRoundsSum, 10)
      ? 'PreviousRoundsDiff'
      : 'Diff'
    const getTotal = (diff) => ({}).toString.call(diff) == '[object Object]'
      ? arraySum(Object.values(diff))
      : diff

    this.playersByClasses.order.forEach(className => {
      const players = this.playersByClasses.byName[className]
      players.order.sort((a, b) => {
        const playerA = players.byName[a]
        const playerB = players.byName[b]

        if (!missingResults(playerA) && missingResults(playerB))
          return -1
        if (missingResults(playerA) && !missingResults(playerB))
          return 1
        if (!missingCompetitions(playerA) && missingCompetitions(playerB))
          return -1
        if (missingCompetitions(playerA) && !missingCompetitions(playerB))
          return 1

        if (getTotal(playerA[prop]) < getTotal(playerB[prop]))
          return -1
        if (getTotal(playerA[prop]) > getTotal(playerB[prop]))
          return 1

        if (players.order.indexOf(a) < players.order.indexOf(b))
          return -1
        if (players.order.indexOf(a) > players.order.indexOf(b))
          return 1
        
        return 0
      })
    })
  }
  /**
   * Ranks players (sets “standing” property). Mutates this.playersByClasses.
   */
  rankPlayers() {
    const { Competition } = getDeepProp(this.state, 'data.results')

    if (!Competition || !this.playersByClasses)
      return

    const prop = Competition.hasOwnProperty('ShowPreviousRoundsSum') && !!parseInt(Competition.ShowPreviousRoundsSum, 10)
      ? 'PreviousRoundsDiff'
      : 'Diff'

    this.playersByClasses.order.forEach(className => {
      let standing = 0
        , prevTotalDiff = 0
      
      this.playersByClasses.byName[className].order.forEach(playerName => {
        const player = this.playersByClasses.byName[className].byName[playerName]
        const totalDiff = ({}).toString.call(player[prop]) == '[object Object]'
          ? arraySum(Object.values(player[prop]))
          : player[prop]
        
        if (totalDiff != prevTotalDiff)
          standing++
        
        prevTotalDiff = totalDiff
        player.standing = standing
      })
    })
  }
  /**
   * Filters players by any user selected filter criteria and returns an object
   * containing players by classes. Does not mutate this.playersByClasses by 
   * creating a deep copy of it.
   */
  filterPlayers() {
    const playersByClasses = deepCopy(this.playersByClasses)
    const filterMap = {
      players: 'Name',
      classes: 'ClassName',
      groups: 'Group'
    }
    
    const isFilterActive = (filterName) => filterName != 'players'
      ? this.state.filters[filterName] != 'all'
      : Array.isArray(this.state.filters.players.selected) &&
        this.state.filters.players.selected.indexOf('all') < 0

    // if any of the filters is not set to "all"
    for (let filterName in this.state.filters)
      if (isFilterActive(filterName)) {
        playersByClasses.order.forEach(className => {
          const players = playersByClasses.byName[className]
          
          players.order = players.order.filter((playerName) => {
            const playerPropVal = players.byName[playerName][filterMap[filterName]]

            return filterName != 'players'
              ? this.state.filters[filterName] == playerPropVal
              : Array.isArray(this.state.filters.players.selected)
                && this.state.filters.players.selected.indexOf(playerPropVal) > -1
          })
          playersByClasses.byName[className] = players
        })
        break // only one filter may be active at a time
      }

    return playersByClasses
  }
  getSubcompetitionCell(competitionKey) {
    const [date, time] = competitionKey.split('T')
    const [y, m, d] = date.split('-')
    const [H, i] = time.split(':')
    const competitionKeyStr = `${m}/${d}/${y} ${H}:${i}`
    return <td>{competitionKeyStr}</td>
  }
  getScoreCell(player, competitionKey, score, idx) {
    const { Competition } = this.state.data.results
    const trackNum = getDeepProp(Competition, ['Tracks', idx, 'Number']) || idx+1
    const key = [Competition.ID, (player.UserID || player.Name), competitionKey, 'hole-'+trackNum+'score'].join('/')
    const scoreClass = this.getScoreClass(score)
    const replacements = [
      trackNum,
      score.Diff,
      scoreClass.title,
      scoreClass.isOB
        ? window.skoorinResults.l10n.score_tooltip_ob.replace(/&#013;/g, '\n')
        : ''
    ]
    let mIdx = -1
    const titleAtt = window.skoorinResults.l10n.score_tooltip
      .replace(/&#013;/g, '\n')
      .replace(/%s/g, () => {
        mIdx++
        return replacements[mIdx]
      })
    return <td key={key} className={scoreClass.class} title={titleAtt}>{score.Result || ''}</td>
  }
  getScoreClass({ Result, Diff, OB }) {
    let className
    const isOB = !!Number(OB)
    const obClass = isOB ? ' ob' : ''

    if (typeof Result === 'undefined')
      className = 'no-score'
    else {
      if (Result == 1)
        className = 'hole-in-one'
      else
        switch (Diff) {
          case -2:
            className = 'eagle'
            break
          case -1:
            className = 'birdie'
            break
          case 0:
            className = 'par'
            break
          case 1:
            className = 'bogey'
            break
          case 2:
            className = 'double-bogey'
            break
          default:
            className = Diff < -2 ? 'eagle' : 'fail'
        }
    }

    return {
      title: window.skoorinResults.l10n.score_terms[className],
      isOB,
      class: className+obClass
    }
  }
  getExtraRows(player, competitionKey, hasSubcompetitions, showPreviousRoundsSum) {
    const { Competition } = this.state.data.results

    return Object.keys(extras).map(extraKey => {
      const key = [Competition.ID, competitionKey, (player.UserID || player.Name), extraKey].join('/')
      return <tr key={key} className={`extra ${extraKey.toLowerCase()}`}>
        <td colSpan={hasSubcompetitions ? 1 : 2}>{window.skoorinResults.l10n.extra[extraKey]}</td>
        {player.PlayerResults[competitionKey].map((score, idx) =>
          <td key={`${key}/${idx}`}>{this.getExtraNotation(score, extraKey)}</td>
        )}
        <td colSpan="2">{player[`${extraKey == 'PEN' ? 'Penalties' : extraKey}Total`] || ''}</td>
        {hasSubcompetitions || !showPreviousRoundsSum ? '' :
          <td colSpan="2">{player[`PreviousRounds${extraKey == 'PEN' ? 'Penalties' : extraKey}`] || ''}</td>
        }
      </tr>
    })
  }
  getExtraNotation(score, key) {
    const value = parseInt(score[key], 10)
    switch (extras[key].type) {
      case Boolean:
        return !!value ? '✔' : ''
      case Number:
      default:
        return !!value ? value : ''
    }
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
