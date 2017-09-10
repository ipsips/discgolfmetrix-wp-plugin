import fetch from './fetch'

export default store => next => action => {
  if (action.type !== 'API_REQ' || !action.payload)
    return next(action)

  const [ request, success, failure ] = action.payload.types
  
  next({
    type: request,
    payload: action.payload
  })

  return callApi(action.payload, store).then(
    response => next({
      type: success,
      payload: action.payload,
      response
    }),
    error => next({
      type: failure,
      payload: action.payload,
      error
    })
  )
}

function callApi(payload, store) {
  const url = `https://discgolfmetrix.com/api.php?${payload.query}`
  const opts = {
    credentials: 'same-origin',
    method: payload.verb || (payload.data ? 'POST' : 'GET'),
    headers: {
      'Accept': 'application/json'
    }
  }

  if (payload.data) {
    Object.assign(opts.headers, {
      'Content-Type': 'application/x-www-form-urlencoded'
    })
    opts.body = payload.data
  }

  return fetch(url, opts).then(parseResponse)
}

function parseResponse(response) {
  return response.ok
    ? response.json()
    : response.json().then(
      (e) => throwResponseError(response, e),
      () => throwResponseError(response)
    )
}

function throwResponseError(response, errJson) {
  throw Object.assign({
    status: response.status,
    statusText: response.statusText,
    message: response.statusText
  }, errJson)
}
