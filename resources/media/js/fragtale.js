/**
 * JS base class for FragTale 2 framework
 * @author Fabrice Dant
 * @typedef {object} FragTale
 * @property {object} Ajax
 * @property {object} Cookie
 * @property {object} Form
 * @property {object} HtmlElement
 */
/**
 * @typedef {object} FragTale.Ajax
 * @typedef {object} FragTale.Cookie
 * @typedef {object} FragTale.Form
 * @typedef {object} FragTale.HtmlElement
 */
var FragTale = {
	TemplateFormat: Object.freeze({ "HTML": 1, "HTML_NO_LAYOUT": 2, "JSON": 3, "PLAIN_TEXT": 4, "XML": 5, "MEDIA": 6, "HTML_DEBUG": 7 }),
	Cookie: {
		/**
		 * Get cookie value, giving cookie name.
		 * @param {string} name - The cookie name
		 * @return {string}
		 */
		get: (name) => {
			return document.cookie.split(';').find(row => row.trim().startsWith(name + '=')).split('=')[1];
		},
		/**
		 * Set cookie value, giving cookie name and its value.
		 * @param {string} name - The cookie name
		 * @param {string} value - The cookie value
		 * @return {FragTale.Cookie} FragTale.Cookie
		 */
		set: (name, value) => {
			document.cookie = name + "=" + value;
			return FragTale.Cookie;
		}
	},
	Ajax: {
		/**
		 * Allows keeping session alive via Ajax calls.
		 * @type boolean
		 */
		with_credentials: true,
		/**
		 * Send Ajax requests giving expected arguments.
		 * @param {string} method - 'get', 'post', 'put' etc...
		 * @param {string} url - The requested URL
		 * @param {?object=} Params (optional) - JSON parameters sent to the endpoint
		 * @param {?string|number=} response_template_format - One of the template format listed in FragTale.TemplateFormat (it can be wether the id or the key name). By default: JSON
		 * @param {?function=} callbackOnSuccess (optional) - Callback function called after Ajax response, on success. Take @param {?XMLHttpRequest=} HttpRequest
		 * @param {?function=} callbackOnError (optional) - Callback function called after Ajax response, on error. Take @param {?XMLHttpRequest=} HttpRequest
		 * @return {FragTale.Ajax} FragTale.Ajax
		 */
		request: (method, url, Params, response_template_format, callbackOnSuccess, callbackOnError) => {
			let HttpRequest = new XMLHttpRequest();
			if (!Params || typeof Params !== 'object')
				Params = {};
			if (!response_template_format)
				response_template_format = FragTale.TemplateFormat.JSON;
			let template_format_id = ('template_format_id' in Params) ? Params.template_format_id : (
				Object.values(FragTale.TemplateFormat).indexOf(response_template_format) > -1 ? response_template_format : (
					response_template_format in FragTale.TemplateFormat ? FragTale.TemplateFormat[response_template_format] : null
				)
			);
			if (!template_format_id || Object.values(FragTale.TemplateFormat).indexOf(template_format_id) === -1) {
				template_format_id = FragTale.TemplateFormat.JSON;
			}
			if (url.indexOf(location.origin) > -1)
				Params.template_format_id = template_format_id;

			HttpRequest.responseType = (template_format_id === FragTale.TemplateFormat.JSON) ? 'json' : 'text';
			HttpRequest.onreadystatechange = () => {
				if (HttpRequest.readyState == 4) {
					if (HttpRequest.status == 200) {
						if (typeof callbackOnSuccess === 'function')
							callbackOnSuccess(HttpRequest);
						else
							FragTale.Form.defaultSuccessCallback(HttpRequest);
					} else if (typeof callbackOnError === 'function')
						callbackOnError(HttpRequest);
					else
						FragTale.Form.defaultErrorCallback(HttpRequest);
				}
			};
			if (method.toLowerCase() === 'get') {
				let strParams = FragTale.convertParamsToQueryString(Params);
				if (strParams) {
					if (url.indexOf('?') > -1)
						url += '&' + strParams;
					else
						url += '?' + strParams;
				}
			}
			HttpRequest.open(method, url);
			HttpRequest.withCredentials = FragTale.Ajax.with_credentials;
			if (method.toLowerCase() === 'post') {
				HttpRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			}
			HttpRequest.send(FragTale.convertParamsToQueryString(Params));
			return FragTale.Ajax;
		},
		/**
		 * Send Ajax requests giving expected arguments, using method "GET"
		 * @param {string} url - The requested URL
		 * @param {?object=} Params (optional) - JSON parameters sent to the endpoint
		 * @param {?string|number=} response_template_format - One of the template format listed in FragTale.TemplateFormat (it can be wether the id or the key name). By default: JSON
		 * @param {?function=} callbackOnSuccess (optional) - Callback function called after Ajax response, on success. Take @param {?XMLHttpRequest=} HttpRequest
		 * @param {?function=} callbackOnError (optional) - Callback function called after Ajax response, on error. Take @param {?XMLHttpRequest=} HttpRequest
		 * @return {FragTale.Ajax} FragTale.Ajax
		 */
		get: (url, Params, response_template_format, callbackOnSuccess, callbackOnError) => { return FragTale.Ajax.request('GET', url, Params, response_template_format, callbackOnSuccess, callbackOnError); },
		/**
		 * Send Ajax requests giving expected arguments, using method "POST"
		 * @param {string} url - The requested URL
		 * @param {?object=} Params (optional) - JSON parameters sent to the endpoint
		 * @param {?string|number=} response_template_format - One of the template format listed in FragTale.TemplateFormat (it can be wether the id or the key name). By default: JSON
		 * @param {?function=} callbackOnSuccess (optional) - Callback function called after Ajax response, on success. Take @param {?XMLHttpRequest=} HttpRequest
		 * @param {?function=} callbackOnError (optional) - Callback function called after Ajax response, on error. Take @param {?XMLHttpRequest=} HttpRequest
		 * @return {FragTale.Ajax} FragTale.Ajax
		 */
		post: (url, Params, response_template_format, callbackOnSuccess, callbackOnError) => { return FragTale.Ajax.request('POST', url, Params, response_template_format, callbackOnSuccess, callbackOnError); },
		/**
		 * Send Ajax requests giving expected arguments, using method "PUT"
		 * @param {string} url - The requested URL
		 * @param {?object=} Params (optional) - JSON parameters sent to the endpoint
		 * @param {?string|number=} response_template_format - One of the template format listed in FragTale.TemplateFormat (it can be wether the id or the key name). By default: JSON
		 * @param {?function=} callbackOnSuccess (optional) - Callback function called after Ajax response, on success. Take @param {?XMLHttpRequest=} HttpRequest
		 * @param {?function=} callbackOnError (optional) - Callback function called after Ajax response, on error. Take @param {?XMLHttpRequest=} HttpRequest
		 * @return {FragTale.Ajax} FragTale.Ajax
		 */
		put: (url, Params, response_template_format, callbackOnSuccess, callbackOnError) => { return FragTale.Ajax.request('PUT', url, Params, response_template_format, callbackOnSuccess, callbackOnError); },
		/**
		 * Send Ajax requests giving expected arguments, using method "DELETE"
		 * @param {string} url The requested URL
		 * @param {object} Params JSON parameters sent to the endpoint
		 * @param {?string|number=} response_template_format - One of the template format listed in FragTale.TemplateFormat (it can be wether the id or the key name). By default: JSON
		 * @param {?function=} callbackOnSuccess (optional) - Callback function called after Ajax response, on success. Take @param {?XMLHttpRequest=} HttpRequest
		 * @param {?function=} callbackOnError (optional) - Callback function called after Ajax response, on error. Take @param {?XMLHttpRequest=} HttpRequest
		 * @return {FragTale.Ajax}
		 */
		delete: (url, Params, response_template_format, callbackOnSuccess, callbackOnError) => { return FragTale.Ajax.request('DELETE', url, Params, response_template_format, callbackOnSuccess, callbackOnError); },
		/**
		 * Send Ajax requests giving expected arguments, using method "CONNECT"
		 * @param {string} url - The requested URL
		 * @param {?object=} Params (optional) - JSON parameters sent to the endpoint
		 * @param {?string|number=} response_template_format - One of the template format listed in FragTale.TemplateFormat (it can be wether the id or the key name). By default: JSON
		 * @param {?function=} callbackOnSuccess (optional) - Callback function called after Ajax response, on success. Take @param {?XMLHttpRequest=} HttpRequest
		 * @param {?function=} callbackOnError (optional) - Callback function called after Ajax response, on error. Take @param {?XMLHttpRequest=} HttpRequest
		 * @return {FragTale.Ajax} FragTale.Ajax
		 */
		connect: (url, Params, response_template_format, callbackOnSuccess, callbackOnError) => { return FragTale.Ajax.request('CONNECT', url, Params, response_template_format, callbackOnSuccess, callbackOnError); },
		/**
		 * Send Ajax requests giving expected arguments, using method "HEAD"
		 * @param {string} url - The requested URL
		 * @param {?object=} Params (optional) - JSON parameters sent to the endpoint
		 * @param {?string|number=} response_template_format - One of the template format listed in FragTale.TemplateFormat (it can be wether the id or the key name). By default: JSON
		 * @param {?function=} callbackOnSuccess (optional) - Callback function called after Ajax response, on success. Take @param {?XMLHttpRequest=} HttpRequest
		 * @param {?function=} callbackOnError (optional) - Callback function called after Ajax response, on error. Take @param {?XMLHttpRequest=} HttpRequest
		 * @return {FragTale.Ajax} FragTale.Ajax
		 */
		head: (url, Params, response_template_format, callbackOnSuccess, callbackOnError) => { return FragTale.Ajax.request('HEAD', url, Params, response_template_format, callbackOnSuccess, callbackOnError); },
		/**
		 * Send Ajax requests giving expected arguments, using method "OPTIONS"
		 * @param {string} url - The requested URL
		 * @param {?object=} Params (optional) - JSON parameters sent to the endpoint
		 * @param {?string|number=} response_template_format - One of the template format listed in FragTale.TemplateFormat (it can be wether the id or the key name). By default: JSON
		 * @param {?function=} callbackOnSuccess (optional) - Callback function called after Ajax response, on success. Take @param {?XMLHttpRequest=} HttpRequest
		 * @param {?function=} callbackOnError (optional) - Callback function called after Ajax response, on error. Take @param {?XMLHttpRequest=} HttpRequest
		 * @return {FragTale.Ajax} FragTale.Ajax
		 */
		options: (url, Params, response_template_format, callbackOnSuccess, callbackOnError) => { return FragTale.Ajax.request('OPTIONS', url, Params, response_template_format, callbackOnSuccess, callbackOnError); },
		/**
		 * Send Ajax requests giving expected arguments, using method "PATCH"
		 * @param {string} url - The requested URL
		 * @param {?object=} Params (optional) - JSON parameters sent to the endpoint
		 * @param {?string|number=} response_template_format - One of the template format listed in FragTale.TemplateFormat (it can be wether the id or the key name). By default: JSON
		 * @param {?function=} callbackOnSuccess (optional) - Callback function called after Ajax response, on success. Take @param {?XMLHttpRequest=} HttpRequest
		 * @param {?function=} callbackOnError (optional) - Callback function called after Ajax response, on error. Take @param {?XMLHttpRequest=} HttpRequest
		 * @return {FragTale.Ajax} FragTale.Ajax
		 */
		patch: (url, Params, response_template_format, callbackOnSuccess, callbackOnError) => { return FragTale.Ajax.request('PATCH', url, Params, response_template_format, callbackOnSuccess, callbackOnError); },
		/**
		 * Send Ajax requests giving expected arguments, using method "TRACE"
		 * @param {string} url - The requested URL
		 * @param {?object=} Params (optional) - JSON parameters sent to the endpoint
		 * @param {?string|number=} response_template_format - One of the template format listed in FragTale.TemplateFormat (it can be wether the id or the key name). By default: JSON
		 * @param {?function=} callbackOnSuccess (optional) - Callback function called after Ajax response, on success. Take @param {?XMLHttpRequest=} HttpRequest
		 * @param {?function=} callbackOnError (optional) - Callback function called after Ajax response, on error. Take @param {?XMLHttpRequest=} HttpRequest
		 * @return {FragTale.Ajax} FragTale.Ajax
		 */
		trace: (url, Params, response_template_format, callbackOnSuccess, callbackOnError) => { return FragTale.Ajax.request('TRACE', url, Params, response_template_format, callbackOnSuccess, callbackOnError); }
	},
	/**
	 * @param {object} Params - Object containing JSON parameters to be converted to query string
	 * @return {string}
	 */
	convertParamsToQueryString: (Params) => {
		if (!Params || !Object.keys(Params).length)
			return null;
		let str_params = '';
		if (typeof Params === 'object') {
			let ConvertedParams = [];
			for (let key in Params) {
				let param = Params[key];
				if (typeof param === 'object' && param) {
					FragTale.buildRecursiveParamsToQueryString(key, param, ConvertedParams);
				} else
					ConvertedParams.push(key + '=' + encodeURIComponent(decodeURIComponent(param)));
			}
			str_params = ConvertedParams.join('&');
		} else
			str_params = Params;
		return str_params.trim('&');
	},
	/**
	 * Part of "FragTale.convertParamsToQueryString" executed for recursive nodes.
	 * @param {string} parent_key - Key of parent JSON node
	 * @param {object} Params - Object containing JSON parameters to be converted to query string
	 * @param {array} array_to_push - Array in which results will be pushed
	 * @return {FragTale}
	 */
	buildRecursiveParamsToQueryString: (parent_key, Params, array_to_push) => {
		if (typeof Params === 'object' && Params) {
			for (let key in Params) {
				let param = Params[key];
				if (typeof param === 'object' && param)
					FragTale.buildRecursiveParamsToQueryString(parent_key + '[' + key + ']', param, array_to_push);
				else
					array_to_push.push(parent_key + '[' + key + ']=' + encodeURIComponent(decodeURIComponent(param)));
			}
		}
		return FragTale;
	},
	Form: {
		/**
		 * Get all form's inputs passed into an object
		 * @param {HTMLElement} FormElement - The whole form element passed via document.getElementById or document.querySelector. This form MUST have property "action" set to the URL on which data are posted.
		 * @return {object} Input Variables
		 */
		getVars: (FormElement) => {
			let InputVars = {};
			FormElement.querySelectorAll("input,select,textarea").forEach((Element) => {
				switch (Element.type) {
					case 'date':
						InputVars[Element.name] = Element.valueAsDate;
						break;
					case 'number':
						InputVars[Element.name] = Element.valueAsNumber;
						break;
					case 'checkbox':
						if (Element.checked) {
							InputVars[Element.name + '[]'] = Element.value;
						}
						break;
					case 'radio':
						if (Element.checked) {
							InputVars[Element.name] = Element.value;
						}
						break;
					case 'select-multiple':
						Element.getElementsByTagName('option').forEach((opt) => {
							if (opt.selected) {
								InputVars[Element.name + '[]'] = Element.value;
							}
						});
						break;
					default:
						InputVars[Element.name] = Element.value;
				}
			});
			return InputVars;
		},
		/**
		 * FragTale.Form.sendData always expects response to be JSON
		 * @param {string} method - Usually, "post"
		 * @param {HTMLElement} FormElement - The whole form element passed via document.getElementById or document.querySelector. This form MUST have property "action" set to the URL on which data are posted.
		 * @param {?function=} callbackOnSuccess (optional) - Callback function called after Ajax response, on success. Take @param {?XMLHttpRequest=} HttpRequest
		 * @param {?function=} callbackOnError (optional) - Callback function called after Ajax response, on error. Take @param {?XMLHttpRequest=} HttpRequest
		 * @return {FragTale.Form} FragTale.Form
		 */
		sendData: (method, FormElement, callbackOnSuccess, callbackOnError) => {
			if (!method)
				method = 'POST';
			let body = document.getElementsByTagName('body')[0];
			body.classList.add('FragTale-loading');
			let layer = document.createElement('div');
			layer.id = 'FragTale-layer';
			body.appendChild(layer);
			FragTale.Ajax.with_credentials = true;
			let url = FormElement.action ? FormElement.action : document.location;
			FragTale.Ajax.request(method, url, FragTale.Form.getVars(FormElement), 'json', callbackOnSuccess, callbackOnError);
			return FragTale.Form;
		},
		/**
		 * Submit form via AJAX using "post" method
		 * @param {HTMLElement} FormElement - The whole form element passed via document.getElementById or document.querySelector. This form MUST have property "action" set to the URL on which data are posted.
		 * @param {function=} callbackOnSuccess - Optional, executed after Ajax response, on success
		 * @param {function=} callbackOnError - Optional, executed after Ajax response, on error
		 * @return {FragTale.Form} FragTale.Form
		 */
		post: (FormElement, callbackOnSuccess, callbackOnError) => {
			return FragTale.Form.sendData('POST', FormElement, callbackOnSuccess, callbackOnError);
		},
		/**
		 * Submit form via AJAX using "put" method
		 * @param {HTMLElement} FormElement - The whole form element passed via document.getElementById or document.querySelector. This form MUST have property "action" set to the URL on which data are put.
		 * @param {function=} callbackOnSuccess - Optional, executed after Ajax response, on success
		 * @param {function=} callbackOnError - Optional, executed after Ajax response, on error
		 * @return {FragTale.Form} FragTale.Form
		 */
		put: (FormElement, callbackOnSuccess, callbackOnError) => {
			return FragTale.Form.sendData('PUT', FormElement, callbackOnSuccess, callbackOnError);
		},
		/**
		 * Submit form via AJAX using "delete" method
		 * @param {HTMLElement} formElement - The whole form element passed via document.getElementById or document.querySelector. This form MUST have property "action" set to the URL on which data are deleted.
		 * @param {function=} callbackOnSuccess - Optional, executed after Ajax response, on success
		 * @param {function=} callbackOnError - Optional, executed after Ajax response, on error
		 * @return {FragTale.Form} FragTale.Form
		 */
		delete: (FormElement, callbackOnSuccess, callbackOnError) => {
			return FragTale.Form.sendData('DELETE', FormElement, callbackOnSuccess, callbackOnError);
		},
		/**
		 * Execute default framework callback on success
		 * @param {XMLHttpRequest} HttpRequest - Native XMLHttpRequest JS object after request's response
		 * @return {FragTale.Form} FragTale.Form
		 */
		defaultSuccessCallback: (HttpRequest) => {
			let Layer = document.getElementById('FragTale-layer');
			let layerToInclude = false;
			if (!Layer) {
				Layer = document.createElement('div');
				Layer.id = 'FragTale-layer';
				layerToInclude = true;
			}
			Layer.onclick = () => {
				Layer.classList.add('FragTale-fadeOut');
				setTimeout(() => Layer.remove(), 200);
			};
			let Popin = document.createElement('div');
			Popin.className = 'FragTale-popin';
			let PopinText = document.createElement('div');
			PopinText.className = 'FragTale-popin-text';

			if (typeof HttpRequest.response !== 'undefined') {
				let Response = HttpRequest.response;
				switch (HttpRequest.responseType.toLowerCase()) {
					case 'json':
						if (typeof Response == 'object') {
							let status = Response.status.toString().toLowerCase();
							if (typeof Response.status !== 'undefined') {
								if (status === 'success')
									Popin.classList.add('FragTale-success');
								else if (status === 'error')
									Popin.classList.add('FragTale-error');
							}
							if (typeof Response.message !== 'undefined' && Response.message) {
								PopinText.append(Response.message.toString());
								Popin.appendChild(PopinText);
							} else {
								// No message to display, just close layer
								console.warn('Server did not send message, nothing might have happened', Response);
								Layer.remove();
							}
						} else if (typeof HttpRequest.responseText !== 'undefined') {
							PopinText.append(HttpRequest.responseText.toString());
							Popin.appendChild(PopinText);
						}
						break;
					case 'text':
					case '':
						if (typeof HttpRequest.responseText !== 'undefined')
							Popin.append(HttpRequest.responseText);
						else
							Popin.append(Response);
						break;
					default:
						console.warn('Unhandled responseType: ' + HttpRequest.responseType, HttpRequest);
				}
				if (layerToInclude)
					document.body.append(Layer);
				Layer.appendChild(Popin);
				Popin.classList.add('FragTale-fadeIn');
				if (typeof Response.redirect !== 'undefined') {
					let redirectTimeout = typeof Response.redirectTimeout !== 'undefined' && Number.isInteger(Response.redirectTimeout) ? Response.redirectTimeout : 1000;
					setTimeout(() => { document.location = Response.redirect; }, redirectTimeout);
				}
			} else {
				console.error('Unexpected Ajax response', HttpRequest);
				Layer.remove();
			}
			document.getElementsByTagName('body')[0].classList.remove('FragTale-loading');
			return FragTale.Form;
		},
		/**
		 * Execute default framework callback on error
		 * @param {XMLHttpRequest} HttpRequest - Native XMLHttpRequest JS object after request's response
		 * @return {FragTale.Form} FragTale.Form
		 */
		defaultErrorCallback: (HttpRequest) => {
			document.getElementsByTagName('body')[0].classList.remove('FragTale-loading');
			let FragTaleLayer = document.getElementById('FragTale-layer');
			if (FragTaleLayer)
				FragTaleLayer.remove();
			console.error('Ajax request error', HttpRequest);
			console.error(HttpRequest.statusText);
			return FragTale.Form;
		}
	},
	HtmlElement: {
		/**
		 * Execute a smooth scroll by default to given HTML element ID
		 * @param {string} element_id - HTML element ID
		 * @return {boolean} Constantly false
		 */
		scrollIntoViewSmoothly: (element_id) => {
			document.getElementById(element_id).scrollIntoView({
				behavior: "smooth",
				block: "start", // vertical alignment
				inline: "start" // horizontal alignment
			});
			return false;
		}
	}
};