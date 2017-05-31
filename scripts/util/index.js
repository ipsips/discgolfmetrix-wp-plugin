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
 * Returns either an array of selected option values or a string 'all'
 */
export function getMultiSelectValue(el) {
  const selected = (el.selectedOptions
    ? [...el.selectedOptions]
    : [...el.options].filter(o => o.selected)
  ).map(o => o.value)

  return selected.indexOf('all') < 0
    ? selected
    : 'all'
}
