import fetch from './fetch'

export default store => next => action => {
  if (action.type !== 'API_REQ' || !action.payload)
    return next(action)

  const [ request, success, failure ] = action.payload.types
  
  next({
    type: request,
    payload: action.payload
  })

  return _callApi(action.payload, store)
    .then(
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

function _callApi(payload, store) {
  /**
   * @todo Temp
   */
  payload.data = `action=skoorin_get_results&${payload.query}`
  const url = window.skoorinResults.ajax_url // `https://skoorin.com/api.php?${payload.query}`
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

  return fetch(url, opts).then(_parseResponse)
}

function _parseResponse(response) {
  return response.ok
    ? response.json()
    : response.json().then(
      (e) => _throwResponseError(response, e),
      () => _throwResponseError(response)
    )
}

function _throwResponseError(response, errJson) {
  throw Object.assign({
    status: response.status,
    statusText: response.statusText,
    message: response.statusText
  }, errJson)
}