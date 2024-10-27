export class ComponentRequestData {
  constructor() {
    this._events = ''
    this._selector = ''

    this._beforeRequest = null
    this._responseHandler = null

    this._beforeResponse = null
    this._afterResponse = null

    this._errorCallback = null

    this._extraProperties = {}
  }

  get events() {
    return this._events
  }

  withEvents(value) {
    this._events = value

    return this
  }

  get selector() {
    return this._selector
  }

  withSelector(value) {
    this._selector = value

    return this
  }

  get beforeRequest() {
    return this._beforeRequest
  }

  hasBeforeRequest() {
    return this._beforeRequest !== null && this._beforeRequest
  }

  withBeforeRequest(value) {
    this._beforeRequest = value

    return this
  }

  get beforeResponse() {
    return this._beforeResponse
  }

  hasBeforeResponse() {
    return this._beforeResponse !== null && typeof this._beforeResponse === 'function'
  }

  withBeforeResponse(value) {
    this._beforeResponse = value

    return this
  }

  get responseHandler() {
    return this._responseHandler
  }

  hasResponseHandler() {
    return this._responseHandler !== null && this._responseHandler
  }

  withResponseHandler(value) {
    this._responseHandler = value

    return this
  }

  get afterResponse() {
    return this._afterResponse
  }

  hasAfterResponse() {
    return this._afterResponse !== null && typeof this._afterResponse === 'function'
  }

  withAfterResponse(value) {
    this._afterResponse = value

    return this
  }

  get errorCallback() {
    return this._errorCallback
  }

  hasErrorCallback() {
    return this._errorCallback !== null && typeof this._errorCallback === 'function'
  }

  withErrorCallback(value) {
    this._errorCallback = value

    return this
  }

  get extraProperties() {
    return this._extraProperties
  }

  withExtraProperties(value) {
    this._extraProperties = value

    return this
  }

  fromDataset(dataset = {}) {
    return this.withEvents(dataset.asyncEvents ?? '')
      .withSelector(dataset.asyncSelector ?? '')
      .withResponseHandler(dataset.asyncCallback ?? null)
      .withBeforeRequest(dataset.asyncBeforeFunction ?? null)
  }

  fromObject(object = {}) {
    return this.withEvents(object.events ?? '')
      .withSelector(object.selector ?? '')
      .withBeforeRequest(object.beforeRequest ?? null)
      .withBeforeResponse(object.beforeResponse ?? null)
      .withResponseHandler(object.responseHandler ?? null)
      .withAfterResponse(object.afterResponse ?? null)
      .withErrorCallback(object.errorCallback ?? null)
      .withExtraProperties(object.extraProperties ?? null)
  }
}
