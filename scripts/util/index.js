// to support array spread op in getMultiSelectValue() below in IE11
import 'core-js/fn/array/from'

export function getDeepProp(obj, path, delim = '.') {
  const arr = path instanceof Array
    ? path
    : path.split(delim)

  return arr.reduce(
    (obj, prop) => obj
      ? obj[prop]
      : obj,
    obj
  )
}

export function setDeepProp(obj, path, value, delim = '.') {
  if (typeof value !== 'undefined' && path.length) {
    const arr = path instanceof Array
      ? path
      : path.split(delim)

    if (arr.length > 1) {
      if (typeof obj[arr[0]] === 'undefined')
        obj[arr[0]] = typeof arr[1] === 'number' ? [] : {}

      setDeepProp(obj[arr[0]], arr.slice(1), value)
      
    } else
      obj[arr[0]] = value
  }

  return obj
}

export function arraySum(arr) {
  return arr.reduce((sum, val) => {
    const num = parseFloat(val)
    return isNaN(num) ? sum : sum + num
  }, 0)
}

/**
 * Basic deep object cloner
 */
export function deepCopy(obj, copy = {}) {
  for(var i in obj)
    copy[i] = typeof obj[i] == "object" && obj[i] !== null
      ? deepCopy(obj[i], obj[i].constructor())
      : obj[i]
  return copy
}

/**
 * Returns an array of selected option values.
 */
export function getMultiSelectValue(el) {
  return (el.selectedOptions
    ? [...el.selectedOptions]
    : [...el.options].filter(o => o.selected)
  ).map(o => o.value)
}

/**
 * https://gist.github.com/Craga89/2829457
 */
export function getIOSVer() {
  return parseFloat(
    ('' + (/CPU.*OS ([0-9_]{1,5})|(CPU like).*AppleWebKit.*Mobile/i.exec(navigator.userAgent) || [0,''])[1])
    .replace('undefined', '3_2').replace('_', '.').replace('_', '')
  ) || false
}
