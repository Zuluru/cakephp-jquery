<?php
namespace ZuluruJquery\View\Helper;

use Cake\Routing\Router;
use Cake\View\Helper;

class JqueryHelper extends Helper {
	public $helpers = ['Html', 'Form'];

	/**
	 * Create a submit button for a form to be submitted via Ajax, e.g. searches.
	 *
	 * The zuluru_ajax_button class is added to the button, along with the data.
	 * zuluru.js attaches an "on click" event handler to these objects, which deals
	 * with the various complexities of issuing and handling the Ajax request.
	 *
	 * $data['disposition'] defaults to "replace_content" if not specified.
	 *
	 * @param string $text The text of the button.
	 * @param array $data Options which dictate how the response is handled. See
	 *      zuluru.js for full documentation of data options.
	 * @param array $button_options Any options for the button itself, passed to the
	 *      form helper. Useful options might include 'class' or 'escape' => false.
	 * @return string
	 */
	public function ajaxButton($text, array $data = [], array $button_options = []) {
		$button_options = $this->addClass($button_options, 'btn-success zuluru_ajax_button');
		foreach ($data as $key => $val) {
			if ($key == 'url' && is_array($val)) {
				$val = Router::url($val);
			}
			$button_options["data-$key"] = $val;
		}

		return $this->Form->button($text, $button_options);
	}

	/**
	 * Create an input field which will make an Ajax request when the value is changed,
	 * e.g. drop-downs that load further selection-dependent fields.
	 *
	 * The zuluru_ajax_input class is added to the link, along with the URL and data.
	 * zuluru.js attaches an "on change" event handler to these objects, which deals
	 * with the various complexities of issuing and handling the Ajax request.
	 *
	 * $data['disposition'] defaults to "replace_content" if not specified.
	 *
	 * @param string $input The name of the input field.
	 * @param array $data Options which dictate how the response is handled. See
	 *      zuluru.js for full documentation of data options.
	 * @param array $input_options Any options for the input itself, passed to the
	 *      form helper. Useful options might include 'label', 'options', 'empty',
	 *      'help', etc.
	 * @return string
	 */
	public function ajaxInput($input, array $data = [], array $input_options = []) {
		$context = $this->Form->context();
		$internalType = $context->type($input);

		$local_options = [];
		foreach ($data as $key => $val) {
			if ($key == 'url' && is_array($val)) {
				$val = Router::url($val);
			}
			$local_options["data-$key"] = $val;
		}

		if ($internalType == 'date') {
			$local_options['data-type'] = 'date';
			$local_options['class'] = 'zuluru_ajax_input';
			$input_options = array_merge($input_options, [
				'year' => $local_options,
				'month' => $local_options,
				'day' => $local_options,
			]);
		} else {
			$input_options = array_merge($this->addClass($input_options, 'zuluru_ajax_input'), $local_options);
		}

		return $this->Form->input($input, $input_options);
	}

	/**
	 * Generate an Ajax link to post to a specific "action" URL, e.g. activate/
	 * deactivate links.
	 *
	 * The zuluru_ajax_link class is added to the link, along with the URL.
	 * zuluru.js attaches an "on click" event handler to these objects, which deals
	 * with the various complexities of issuing and handling the Ajax request.
	 *
	 * $data['url'] is required.
	 * $data['disposition'] defaults to "replace" if not specified.
	 *
	 * @param string $text The text of the link.
	 * @param array $data Options which dictate how the response is handled. See
	 *      zuluru.js for full documentation of data options.
	 * @param array $link_options Any options for the link itself, passed to the
	 *      HTML helper. Useful options might include 'class' or 'escape' => false.
	 * @return string
	 */
	public function ajaxLink($text, array $data = [], array $link_options = []) {
		$link_options = $this->addClass($link_options, 'zuluru_ajax_link');
		$url = $data['url'];
		foreach ($data as $key => $val) {
			if ($key == 'url' && is_array($val)) {
				$val = Router::url($val);
			}
			$link_options["data-$key"] = $val;
		}

		// TODO: Ajax posts currently have CSRF tokens added to them in zuluru.js. It would be better to create forms
		// and post through them, but this also means handling non-Ajax use of those links. When we get around to this,
		// this should be replaced (at least in some cases) with $this->Form->postLink.
		return $this->Html->link($text, $url, $link_options);
	}

	public function autocompleteInput($input, $id, $data) {
		$input_options = [
			'type' => 'text',
			'autocomplete' => 'off', // Browser autocomplete causes confusion
			'label' => false,
			'id' => $id,
			'class' => 'zuluru_autocomplete',
			'size' => 50,
			'secure' => false,
		];

		if (is_array($data['url'])) {
			$input_options['data-url'] = Router::url($data['url']);
		} else {
			$input_options['data-url'] = $data['url'];
		}
		if (array_key_exists('disposition', $data)) {
			$input_options['data-disposition'] = $data['disposition'];
		}
		if (array_key_exists('add_url', $data)) {
			if (is_array($data['add_url'])) {
				$input_options['data-add-url'] = Router::url($data['add_url']);
			} else {
				$input_options['data-add-url'] = $data['add_url'];
			}
			$input_options['data-add-selector'] = $data['add_selector'];
		}

		return $this->Form->input($input, $input_options);
	}

	/*
	 * Generate a widget that contains a number of options, each of which will be
	 * an Ajax link.
	 *
	 * The zuluru_in_place_widget class is added to the widget, along with the URL.
	 * zuluru.js attaches an "on click" event handler to these objects, which deals
	 * with the various complexities of issuing and handling the Ajax request.
	 *
	 * $data['type'] is required, and must simply match the type provided to the
	 *   inPlaceWidgetOptions function.
	 * $data['url'] is required.
	 * $data['disposition'] defaults to "replace" if not specified.
	 * $data['valid-options'], if specified, provides a list of options that may be
	 *   presented in *this* instance of the widget. This is useful when there are
	 *   multiple widgets that share a common set of options, but only a subset are
	 *   valid at each point (for example, attendance changes).
	 *
	 * @param string $text The text of the widget.
	 * @param array $data Options which dictate how the response is handled. See
	 *      zuluru.js for full documentation of data options, but note that in the case
	 *      of in-place widgets, the data options are split across the widget and the
	 *      widget options.
	 * @param array $widget_options Any options for the widget itself, passed to the
	 *      HTML helper. Useful options might include 'class'. 'escape' is always set
	 *      to false.
	 * @param bool $dropdown_icon If set to false, will disable the inclusion of the
	 *      drop-down icon with the widget.
	 * @param array $ajax_data, if specified, will be appended to the URL used for the
	 *      Ajax request. It will *not* be included in the URL used for the non-Ajax link.
	 * @return string
	 */
	public function inPlaceWidget($text, array $data = [], array $widget_options = [], $dropdown_icon = true, array $ajax_data = null) {
		$widget_options = $this->addClass($widget_options, 'zuluru_in_place_widget');

		$url = $data['url'];
		foreach ($data as $key => $val) {
			if ($key == 'url' && is_array($val)) {
				// TODO: This will have to change if we ever need to handle a string URL plus Ajax data
				if (isset($ajax_data)) {
					$val += $ajax_data;
				}
				$val = Router::url($val);
			} else if ($key == 'valid-options') {
				$val = '#' . implode('#', $val) . '#';
			}
			$widget_options["data-$key"] = $val;
		}

		if (!isset($dropdown_icon) || $dropdown_icon === true) {
			$text .= $this->Html->iconImg('dropdown.png');
		}

		return $this->Html->tag('span',
			// TODOBOOTSTRAP: 'class' => 'icon' here is really a bit of a hack. Perhaps elsewhere too.
			$this->Html->link($text, $url, ['escape' => false, 'class' => 'icon']),
			$widget_options
		);
	}

	/**
	 * Generate a collection of options for an in_place_widget, each of which will be
	 * an Ajax link.
	 *
	 * The zuluru_in_place_widget_XXX_options class is added to the widget.
	 * zuluru.js attaches an "on click" event handler to these objects, which deals
	 * with the various complexities of issuing and handling the Ajax request.
	 *
	 * $data['type'] is required, and must simply match the type provided to the
	 *   inPlaceWidget function.
	 * $data['url-param'] is required, and indicates the name of the parameter to be passed.
	 * Either $data['ajax'] must be specified and true, or $data['url'] is required.
	 *
	 * @param array $options An array of value => label pairs. If $data['ajax'] is set,
	 *      each "label" may also be an array, with a required 'text' key indicating the
	 *      label text and an optional 'dialog' key indicating the ID (without #) of the
	 *      dialog to show before submitting the request.
	 * @param array $data Options which dictate how the response is handled. See
	 *      zuluru.js for full documentation of data options, but note that in the case
	 *      of in-place widgets, the data options are split across the widget and the
	 *      widget options.
	 * @param string $prompt If specified, will be included at the top of the list of options.
	 * @return string
	 */
	public function inPlaceWidgetOptions(array $options, array $data = [], $prompt = null) {
		if ($prompt) {
			// TODOBOOTSTRAP: Somehow, don't highlight this. CSS, presumably.
			$div_content = $this->Html->tag('div', h($prompt));
		} else {
			$div_content = '';
		}

		if (isset($data['confirm'])) {
			$confirm = h($data['confirm']);
			unset($data['confirm']);
		} else {
			$confirm = false;
		}

		foreach ($options as $key => $text) {
			if (isset($data['ajax']) && $data['ajax']) {
				$div_options = [
					'id' => "zuluru_in_place_widget_{$data['type']}_option_{$key}",
					'class' => 'zuluru_in_place_widget_option',
					'data-value' => $key,
				];

				if (is_array($text)) {
					if (array_key_exists('dialog', $text)) {
						$div_options['data-dialog'] = $text['dialog'];
					}

					$text = $text['text'];
				}

				// TODOSECOND: Do we really need to __ the text here?
				$div_content .= $this->Html->tag('div', __($text), $div_options);
			} else {
				$data['url'][$data['url-param']] = $key;
				$div_content .= $this->Html->tag('div', $this->Html->link(__($text), $data['url']));
			}
		}

		return $this->Html->tag('div', $div_content, [
			'id' => "zuluru_in_place_widget_{$data['type']}_options",
			'class' => 'zuluru_in_place_widget_options',
			'data-param' => $data['url-param'],
			'data-confirm' => $confirm,
			'style' => 'display: none;',
		]);
	}

	/**
	 * Generate a drop-down widget with a list of additional actions
	 *
	 * $data['type'] is required, and must simply be unique on the page; use of an
	 *      id field here is recommended.
	 *
	 * @param array $data Options which dictate how the response is handled. See
	 *      zuluru.js for full documentation of data options.
	 * @param array $actions The list of actions to be displayed.
	 * @param string $text The optional text of the widget.
	 * @param array $widget_options Any options for the widget itself, passed to the
	 *      HTML helper. Useful options might include 'class' or 'escape' = false.
	 * @param bool $dropdown_icon If set to false, will disable the inclusion of the
	 *      drop-down icon with the widget.
	 * @return string
	 */
	public function moreWidget(array $data, array $actions, $text = null, array $widget_options = [], $dropdown_icon = true) {
		if (empty($actions)) {
			return '';
		}

		// First, we create the link
		$widget_options = $this->addClass($widget_options, 'zuluru_in_place_widget');
		$widget_options['data-type'] = $data['type'];
		if (!$text) {
			$text = __('More');
		}
		if ($dropdown_icon) {
			$text .= $this->Html->iconImg('dropdown.png');
		}

		// Generate the hidden div with all the widget options in it
		$div_content = '';
		foreach ($actions as $name => $widget) {
			if (array_key_exists('ajax_data', $widget)) {
				$item = $this->ajaxLink($name, $widget['ajax_data'], ['escape' => false]);
			} else {
				// TODO: Make the non-Ajax version work; will need JS changes?
				$params = [];
				if (array_key_exists('confirm', $widget)) {
					$params['confirm'] = $widget['confirm'];
				}
				if (array_key_exists('method', $widget) && $widget['method'] == 'post') {
					$item = $this->Form->postLink($name, $widget['url'], $params);
				} else {
					$item = $this->Html->link($name, $widget['url'], $params);
				}
			}
			$div_content .= $this->Html->tag('div', $item);
		}

		return $this->Html->tag('span',
			$this->Html->link($text, '#', ['escape' => false]),
			$widget_options
		) . $this->Html->tag('div', $div_content, [
			'id' => "zuluru_in_place_widget_{$data['type']}_options",
			'class' => 'zuluru_in_place_widget_options',
			'style' => 'display: none;',
		]);
	}

	public function selectAll($selector, $text = null) {
		if ($text) {
			$select = __('Select all {0}', $text);
			$unselect = __('Unselect all {0}', $text);
		} else {
			$select = __('Select all');
			$unselect = __('Unselect all');
		}

		return $this->Html->link($select, '#', [
			'class' => 'zuluru_select_all',
			'data-select-text' => $select,
			'data-unselect-text' => $unselect,
			'data-selector' => $selector,
		]);
	}

	/**
	 * Create an input field which will toggle the visibility of certain DOM elements
	 * when the value is changed, e.g. check boxes that trigger other fields to show up.
	 *
	 * The zuluru_toggle_input class is added to the link, along with the selectors.
	 * zuluru.js attaches an "on click" event handler to these objects, which deals
	 * with the various complexities of showing and hiding things.
	 *
	 * Hiding is always done first followed by showing, so you can use a general hide
	 * selector to hide everything and then show specific bits.
	 *
	 * $selector_options['selector'] is most often used with checkbox inputs, though it
	 * can also apply to any input that take on truthy vs non-truthy values (e.g. a text
	 * input where blank or zero differentiates from a non-blank or non-zero entry, or a
	 * select where one value equates to false, most often through use of an input option
	 * like 'empty' => true). It can be an array with "hide" and "show" keys, where hide
	 * specifies a selector to hide when the input is non-truthy and show specifies a
	 * selector to show when it is truthy. It can alternately be a string, specifying a
	 * selector that should be hidden when the value is non-truthy and shown when it is
	 * truthy.
	 *
	 * $selector_options['values'] is typically used with select inputs, and is an array
	 * of key / value pairs where the key is the input value to match and the value is
	 * the selector to show when that value is selected by the user. Note that the key
	 * must match the value attribute of the option tag, not the content of the option
	 * (that is, if the HTML has <option value="ON">Ontario</option>, you need something
	 * like 'values' => ['ON' => '.selector']).
	 *
	 * $selector_options['parent_selector'] may be set, in which case it finds the closest
	 * such selector to the main selector, thus allowing you to show/hide the div, span,
	 * etc. that encloses the given selector. It is often useful to pass '.form-group'
	 * here, so that it hides the label as well as the input.
	 *
	 * $selector_options['parent_selector_optional'] may be set if parent_selector is, in
	 * which case additional checks are done to ensure that elements matched by the main
	 * selector (whether from a selector or a values key) are shown or hidden regardless
	 * of whether they are contained within an element that matches the specified parent
	 * selector.
	 *
	 * $selector_options['container_selector'] may be set if you only want to show/hide
	 * things that are within the element(s) specified.
	 *
	 * @param string $input The name of the input field.
	 * @param array $input_options Any options for the input itself, passed to the
	 *      form helper. Useful options might include 'label', 'options', 'empty',
	 *      'help', etc.
	 * @param array $selector_options Options which dictate what elements will be shown
	 * 	    and hidden based on the input.
	 * @return string
	 */
	public function toggleInput($input, $input_options = [], $selector_options = []) {
		$input_options = $this->addClass($input_options, 'zuluru_toggle_input');
		$new_options = ['class' => $input_options['class']];

		if (array_key_exists('parent_selector', $selector_options)) {
			$new_options['data-parent-selector'] = $selector_options['parent_selector'];
		}
		if (array_key_exists('parent_selector_optional', $selector_options)) {
			$new_options['data-parent-selector-optional'] = $selector_options['parent_selector_optional'];
		}
		if (array_key_exists('container_selector', $selector_options)) {
			$new_options['data-container-selector'] = $selector_options['container_selector'];
		}

		if (array_key_exists('values', $selector_options)) {
			if (isset($input_options['multiple']) && $input_options['multiple'] == 'checkbox') {
				// For the "multiple checkbox" scenario, we need to change the 'options' element
				// to use the 'value' and 'text' parameters, as well as putting the class and
				// selector data in each one. But the selectors in these cases can be simple,
				// because we can have multiple options selected and each one is independent
				// of the others.
				if (!isset($input_options['options'])) {
					trigger_error('No options specified!', E_USER_ERROR);
				}
				$options = [];
				foreach ($input_options['options'] as $value => $text) {
					$option = array_merge(compact('value', 'text'), $new_options);

					if (isset($selector_options['values'][$value])) {
						$selector = $selector_options['values'][$value];
						if (is_array($selector)) {
							if (array_key_exists('hide', $selector)) {
								$option['data-selector-hide'] = $selector['hide'];
							}
							if (array_key_exists('show', $selector)) {
								$option['data-selector-show'] = $selector['show'];
							}
						} else {
							$option['data-selector'] = $selector;
						}
					}

					$options[] = $option;
				}
				$new_options['options'] = $options;
			} else {
				// For the non-checkboxes scenario, each option will need to show its own stuff
				// but also hide everything else. So, we need to use more complex per-value
				// selectors in this cases, as well as providing the full list of all values to
				// iterate through.
				$safe_values = [];
				foreach ($selector_options['values'] as $value => $selector) {
					$value = str_replace(' ', '', $value);
					if ($value == '') {
						$value = 'empty-string';
					}
					$safe_values[] = $value;
					if (is_array($selector)) {
						if (array_key_exists('hide', $selector)) {
							$new_options["data-selector-$value-hide"] = $selector['hide'];
						}
						if (array_key_exists('show', $selector)) {
							$new_options["data-selector-$value-show"] = $selector['show'];
						}
					} else {
						$new_options["data-selector-$value"] = $selector;
					}
				}
				$new_options["data-values"] = implode(' ', $safe_values);

				if (array_key_exists('type', $input_options) && $input_options['type'] == 'radio') {
					foreach ($input_options['options'] as $key => $value_options) {
						if (is_array($value_options)) {
							$input_options['options'][$key] += $new_options;
						} else {
							$input_options['options'][$key] = array_merge($new_options, [
								'value' => $key,
								'text' => $value_options,
							]);
						}
					}
					$new_options = [];
				}
			}
		} else if (array_key_exists('selector', $selector_options)) {
			// Any other input type just needs a single, simple selector.
			if (is_array($selector_options['selector'])) {
				if (array_key_exists('hide', $selector_options['selector'])) {
					$new_options['data-selector-hide'] = $selector_options['selector']['hide'];
				}
				if (array_key_exists('show', $selector_options['selector'])) {
					$new_options['data-selector-show'] = $selector_options['selector']['show'];
				}
			} else {
				$new_options['data-selector'] = $selector_options['selector'];
			}
		} else {
			trigger_error('No selector specified!', E_USER_ERROR);
		}

		return $this->Form->input($input, array_merge($input_options, $new_options));
	}

	/**
	 * Create a link which will toggle the visibility of certain DOM elements.
	 *
	 * The zuluru_toggle_link class is added to the link, along with the selector(s).
	 * zuluru.js attaches an "on click" event handler to these objects, which deals
	 * with toggling the visibility. If "hide" and "show" selectors are given, hiding
	 * is done first followed by showing, so you can use a general hide selector to
	 * hide everything and then show specific bits.
	 */
	public function toggleLink($text, $selector, $link_options = [], $toggle_options = [], $target = '#') {
		$link_options = $this->addClass($link_options, 'zuluru_toggle_link');
		$toggle_options += ['toggle_text' => false];

		if (is_array($selector)) {
			$link_options['data-selector-hide'] = $selector['hide'];
			$link_options['data-selector-show'] = $selector['show'];
		} else {
			$link_options['data-selector'] = $selector;
		}

		if ($toggle_options['toggle_text']) {
			// TODO: Add an option to set initial state. We'll ideally want the JS to recognize this and show or hide as appropriate during page ready event.
			// TODO: Is there a nice way we can handle the advanced/basic gears from the league/division edit pages with this?
			$link_options['data-show-text'] = __('Show {0}', $text);
			$text = $link_options['data-hide-text'] = $text = __('Hide {0}', $text);
		}

		return $this->Html->link($text, $target, $link_options);
	}

	public function toggleLinkPair($a_text, $a_class, $b_text, $b_class, $link_options = []) {
		return $this->toggleLink($a_text, ['hide' => ".$a_class", 'show' => ".$b_class"], array_merge($link_options, ['class' => $a_class])) .
			$this->toggleLink($b_text, ['hide' => ".$b_class", 'show' => ".$a_class"], array_merge($link_options, ['class' => $b_class]));
	}

}
