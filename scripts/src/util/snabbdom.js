import snabbdom           from 'snabbdom'
import snabbdomClass      from 'snabbdom/modules/class'
import snabbdomProps      from 'snabbdom/modules/props'
// import snabbdomStyle      from 'snabbdom/modules/style'
import snabbdomListeners  from 'snabbdom/modules/eventlisteners'

export
const patch = snabbdom.init([
  snabbdomClass,
  snabbdomProps,
  // snabbdomStyle,
  snabbdomListeners
])