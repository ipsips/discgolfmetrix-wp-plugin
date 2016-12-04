/**
 * Throttle resize event.
 * @source developer.mozilla.org/en-US/docs/Web/Events/resize#requestAnimationFrame
 */
window.optimizedResize = (function () {
  var callbacks = []
  var running = false

  function resize() {
    if (!running) {
      running = true

      if (window.requestAnimationFrame)
        window.requestAnimationFrame(runCallbacks)
      else
        setTimeout(runCallbacks, 66)
    }
  }

  function runCallbacks() {
    callbacks.forEach(function(callback) {
      callback()
    })

    running = false
  }

  function addCallback(callback) {
    if (callback)
      callbacks.push(callback)
  }

  return {
    add: function(callback) {
      if (!callbacks.length)
        window.addEventListener('resize', resize)

      addCallback(callback)
    },
    remove: function (callback) {
      var callbackIdx = callbacks.indexOf(callback)

      if (callbackIdx < 0)
        return

      callbacks.splice(callbackIdx, 1)

      if (!callbacks.length)
        window.removeEventListener('resize', resize)
    }
  }
})()