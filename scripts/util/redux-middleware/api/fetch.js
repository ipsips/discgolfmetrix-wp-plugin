/**
 * Disguising XMLHttpRequest as fetch to
 * leverage the progress event hook
 */
export default function fetch(url, options) {
  return new Promise(fulfill => {
    options = Object.assign({
      method: 'GET',
      headers: {}
    }, options)

    const xhr = new XMLHttpRequest()
    const done = () =>
      fulfill({
        ok: 200 <= xhr.status && xhr.status < 300,
        status: xhr.status,
        statusText: xhr.statusText,
        json: () =>
          new Promise((fulfill, reject) => {
            try {
              const json = JSON.parse(xhr.responseText.trim())
              fulfill(json)
            } catch(err) {
              reject(err)
            }
          })
      })
    
    xhr.open(options.method, url)
    // xhr.withCredentials = true

    Object.keys(options.headers).forEach(header =>
      xhr.setRequestHeader(header, options.headers[header])
    )

    xhr.addEventListener('error', done)
    xhr.addEventListener('abort', done)
    xhr.upload.addEventListener('error', done)
    xhr.upload.addEventListener('abort', done)

    xhr.addEventListener('readystatechange', () => {
      if (xhr.readyState === XMLHttpRequest.DONE)
        done()
    })

    if (typeof options.onUploadProgress === 'function')
      xhr.upload.addEventListener('progress', options.onUploadProgress)

    xhr.send(options.body)
  })
}