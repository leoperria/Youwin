/**
 * Search plugin for Ext.grid.GridPanel, Ext.grid.EditorGrid ver. 2.x or subclasses of them
 *
 * @author    Ing. Jozef Sakalos
 * @copyright (c) 2008, by Ing. Jozef Sakalos
 * @date      17. January 2008
 * @version   $Id: Ext.ux.grid.Search.js 220 2008-04-29 21:46:51Z jozo $
 *
 * @license Ext.ux.grid.Search is licensed under the terms of
 * the Open Source LGPL 3.0 license.  Commercial use is permitted to the extent
 * that the code/component(s) do NOT become part of another Open Source or Commercially
 * licensed development library or toolkit without explicit permission.
 * 
 * License details: http://www.gnu.org/licenses/lgpl.html
 * 
 * Revision by Omicronmedia (C) 2009
 * version 1.1
 */

Ext.ns('Ext.ux.grid');

/**
 * @class Ext.ux.grid.Search
 * @extends Ext.util.Observable
 * @param {Object} config configuration object
 * @constructor
 */
Ext.ux.grid.Search = function(config) {
	Ext.apply(this, config);
	Ext.ux.grid.Search.superclass.constructor.call(this);
}; 

Ext.extend(Ext.ux.grid.Search, Ext.util.Observable, {
	/**
	 * cfg {Boolean} autoFocus true to try to focus the input field on each store load (defaults to undefined)
	 */

	/**
	 * @cfg {String} searchText Text to display on menu button
	 */
	 searchText:'Cerca'

	/**
	 * @cfg {String} searchTipText Text to display as input tooltip. Set to '' for no tooltip
	 */ 
	,searchTipText:'Digitare la chiave di ricerca e premere Invio'

	/**
	 * @cfg {String} selectAllText Text to display on menu item that selects all fields
	 */
	,selectAllText:'Seleziona tutti'

	/**
	 * @cfg {String} position Where to display the search controls. Valid values are top and bottom (defaults to bottom)
	 * Corresponding toolbar has to exist at least with mimimum configuration tbar:[] for position:top or bbar:[]
	 * for position bottom. Plugin does NOT create any toolbar.
	 */
	,position:'bottom'

	/**
	 * @cfg {String} iconCls Icon class for menu button (defaults to icon-magnifier)
	 */
	,iconCls:'icon-magnifier'

	/**
	 * @cfg {String/Array} checkIndexes Which indexes to check by default. Can be either 'all' for all indexes
	 * or array of dataIndex names, e.g. ['persFirstName', 'persLastName']
	 */
	,checkIndexes:'all'

	/**
	 * @cfg {Array} disableIndexes Array of index names to disable (not show in the menu), e.g. ['persTitle', 'persTitle2']
	 */
	,disableIndexes:[]

	/**
	 * @cfg {String} dateFormat how to format date values. If undefined (the default) 
	 * date is formatted as configured in colummn model
	 */
	,dateFormat:undefined

	/**
	 * @cfg {Boolean} showSelectAll Select All item is shown in menu if true (defaults to true)
	 */
	,showSelectAll:true

	/**
	 * @cfg {String} menuStyle Valid values are 'checkbox' and 'radio'. If menuStyle is radio
	 * then only one field can be searched at a time and selectAll is automatically switched off.
	 */
	,menuStyle:'checkbox'

	/**
	 * @cfg {Number} minChars minimum characters to type before the request is made. If undefined (the default)
	 * the trigger field shows magnifier icon and you need to click it or press enter for search to start. If it
	 * is defined and greater than 0 then maginfier is not shown and search starts after minChars are typed.
	 */

	/**
	 * @cfg {String} minCharsTipText Tooltip to display if minChars is > 0
	 */
	,minCharsTipText:'Digitare almeno {0} caratteri'

	/**
	 * @cfg {String} mode Use 'remote' for remote stores or 'local' for local stores. If mode is local
	 * no data requests are sent to server the grid's store is filtered instead (defaults to 'remote')
	 */
	,mode:'remote'

	/**
	 * @cfg {Array} readonlyIndexes Array of index names to disable (show in menu disabled), e.g. ['persTitle', 'persTitle2']
	 */

	/**
	 * @cfg {Number} width Width of input field in pixels (defaults to 100)
	 */
	,width:100

	/**
	 * @cfg {String} xtype xtype is usually not used to instantiate this plugin but you have a chance to identify it
	 */
	,xtype:'gridsearch'

	/**
	 * @cfg {Object} paramNames Params name map (defaults to {fields:'fields', query:'query'}
	 */
	,paramNames: {
		 fields:'fields'
		,query:'query'
	}

	/**
	 * @cfg {String} shortcutKey Key to fucus the input field (defaults to r = Sea_r_ch). Empty string disables shortcut
	 */
	,shortcutKey:'r'

	/**
	 * @cfg {String} shortcutModifier Modifier for shortcutKey. Valid values: alt, ctrl, shift (defaults to alt)
	 */
	,shortcutModifier:'alt'
  
  /**
   * @cfg {boolean} showButtonOnly (default to false) (add by Omicronmedia (c) 2009) 
   * Show a button instead of menu, is a useful feature when you have to conduct research in one field.
   * When the button is pressed calls the search function 
   */
  ,showButtonOnly:false
  
  
  /**
   * @cfg (boolean) allowAutoQuery (default to false) (added by Omicronmedia (c) 2009)
   * If is true, when the user types a number of chars>minChars the object make an automatic 
   * Ajax request with these chars
   */
  ,allowAutoQuery:false
  
  /**
   * @cfg (boolean) reloadOnClear (default to false) (added by Omicronmedia (c) 2009)
   * If is true, when the user click on trigger button the onTriggerSearch reload the store
   * (WARNING: the field value is setted on null in the ajax request)
   */
   ,reloadOnClear:false

	/**
	 * @cfg {String} align 'left' or 'right' (defaults to 'left')
	 */

	/**
	 * @cfg {Number} minLength force user to type this many character before he can make a search
	 */

	/**
	 * @cfg {Ext.Panel/String} toolbarContainer Panel (or id of the panel) which contains toolbar we want to render
	 * search controls to (defaults to this.grid, the grid this plugin is plugged-in into)
	 */
	
	// {{{
	/**
	 * private
	 * @param {Ext.grid.GridPanel/Ext.grid.EditorGrid} grid reference to grid this plugin is used for
	 */
	,init:function(grid) {
		this.grid = grid;

		// setup toolbar container if id was given
		if('string' === typeof this.toolbarContainer) {
			this.toolbarContainer = Ext.getCmp(this.toolbarContainer);
		}

		// do our processing after grid render and reconfigure
		grid.onRender = grid.onRender.createSequence(this.onRender, this);
		grid.reconfigure = grid.reconfigure.createSequence(this.reconfigure, this);
	} // eo function init
	// }}}
	// {{{
	/**
	 * private add plugin controls to <b>existing</b> toolbar and calls reconfigure
	 */
	,onRender:function() {
		var panel = this.toolbarContainer || this.grid;
		var tb = 'bottom' === this.position ? panel.bottomToolbar : panel.topToolbar;

		// add menu
		this.menu = new Ext.menu.Menu();

		// handle position
		if('right' === this.align) {
			tb.addFill();
		} else {
			if(0 < tb.items.getCount()) {
				tb.addSeparator();
			}
		}
    // Omicronmedia: if there is only a search item  
    // can choose to render a normal button near the searchfield
    // instead of the menu button
    if(this.showButtonOnly===false){
		  //add menu button
			tb.add({
				 text:this.searchText
				,menu:this.menu
				,iconCls:this.iconCls
			});
    }else{
	    tb.add({
	      text:this.searchText,
	      iconCls:this.iconCls,
	      handler:function(){
          //console.log(this.field.getValue().toString().length);
	        (this.field.getValue().toString().length>=this.minChars) ? this.onTriggerSearch(): null ;
	      },
	      scope:this
	    });
    }
		// add input field (TwinTriggerField in fact)
		this.field = new Ext.form.TwinTriggerField({
			 width:this.width
			,selectOnFocus:undefined === this.selectOnFocus ? true : this.selectOnFocus
			,trigger1Class:'x-form-clear-trigger'
			,trigger2Class:this.minChars ? 'x-hidden' : 'x-form-search-trigger'
			,onTrigger1Click:this.minChars ? this.onTriggerClear.createDelegate(this) : this.onTriggerClear.createDelegate(this)
			,onTrigger2Click:this.onTriggerSearch.createDelegate(this)
			,minLength:this.minLength
		});

		// install event handlers on input field
		this.field.on('render', function() {
			this.field.el.dom.qtip = this.minChars ? String.format(this.minCharsTipText, this.minChars) : this.searchTipText;

			if(this.minChars && this.allowAutoQuery!=false) {
				this.field.el.on({scope:this, buffer:300, keyup:this.onKeyUp});
			}

			// install key map
			var map = new Ext.KeyMap(this.field.el, [{
				 key:Ext.EventObject.ENTER
				,scope:this
				,fn:this.onTriggerSearch
			},{
				 key:Ext.EventObject.ESC
				,scope:this
				,fn:this.onTriggerClear
			}]);
			map.stopEvent = true;
		}, this, {single:true});

		tb.add(this.field);

		// reconfigure
		this.reconfigure();

		// keyMap
		if(this.shortcutKey && this.shortcutModifier) {
			var shortcutEl = this.grid.getEl();
			var shortcutCfg = [{
				 key:this.shortcutKey
				,scope:this
				,stopEvent:true
				,fn:function() {
					this.field.focus();
				}
			}];
			shortcutCfg[0][this.shortcutModifier] = true;
			this.keymap = new Ext.KeyMap(shortcutEl, shortcutCfg);
		}

		if(true === this.autoFocus) {
			this.grid.store.on({scope:this, load:function(){this.field.focus();}});
		}
	} // eo function onRender
	// }}}
	// {{{
	/**
	 * field el keypup event handler. Triggers the search
	 * @private
	 */
	,onKeyUp:function() {
		var length = this.field.getValue().toString().length;
		if(0 === length || this.minChars <= length) {
			this.onTriggerSearch();
		}
	} // eo function onKeyUp
	// }}}
	// {{{
	/**
	 * private Clear Trigger click handler
	 */
	,onTriggerClear:function() {
    if(this.field.getValue()) {
			this.field.setValue('');
			this.field.focus();
			this.onTriggerSearch();
		}
	} // eo function onTriggerClear
	// }}}
	// {{{
	/**
	 * private Search Trigger click handler (executes the search, local or remote)
	 */
	,onTriggerSearch:function() {
		if(!this.field.isValid()) {
      return;
		}
		var val = this.field.getValue();
		var store = this.grid.store;

		// grid's store filter
		if('local' === this.mode) {
			store.clearFilter();
			if(val) {
				store.filterBy(function(r) {
					var retval = false;
					this.menu.items.each(function(item) {
						if(!item.checked || retval) {
							return;
						}
						var rv = r.get(item.dataIndex);
						rv = rv instanceof Date ? rv.format(this.dateFormat || r.fields.get(item.dataIndex).dateFormat) : rv;
						var re = new RegExp(val, 'gi');
						retval = re.test(rv);
					}, this);
					if(retval) {
						return true;
					}
					return retval;
				}, this);
			}
			else {
			}
		}
		// ask server to filter records
		else {
			// clear start (necessary if we have paging)
			if(store.lastOptions && store.lastOptions.params) {
				store.lastOptions.params[store.paramNames.start] = 0;
			}

			// get fields to search array
			var fields = [];
			this.menu.items.each(function(item) {
				if(item.checked) {
					fields.push(item.dataIndex);
				}
			});

			// add fields and query to baseParams of store
			delete(store.baseParams[this.paramNames.fields]);
			delete(store.baseParams[this.paramNames.query]);
			if (store.lastOptions && store.lastOptions.params) {
				delete(store.lastOptions.params[this.paramNames.fields]);
				delete(store.lastOptions.params[this.paramNames.query]);
			}
			if(fields.length) {
				store.baseParams[this.paramNames.fields] = Ext.encode(fields);
				store.baseParams[this.paramNames.query] = val;
			}
      /*Omicronmedia 2009 added reloadOnClear in config options*/
      if(this.field.getValue().toString().length>=this.minChars || this.reloadOnClear){
			 //alert('reload store');
			 store.reload();
      }
		}

	} // eo function onTriggerSearch
	// }}}
	// {{{
	/**
	 * @param {Boolean} true to disable search (TwinTriggerField), false to enable
	 */
	,setDisabled:function() {
		this.field.setDisabled.apply(this.field, arguments);
	} // eo function setDisabled
	// }}}
	// {{{
	/**
	 * Enable search (TwinTriggerField)
	 */
	,enable:function() {
		this.setDisabled(false);
	} // eo function enable
	// }}}
	// {{{
	/**
	 * Enable search (TwinTriggerField)
	 */
	,disable:function() {
		this.setDisabled(true);
	} // eo function disable
	// }}}
	// {{{
	/**
	 * private (re)configures the plugin, creates menu items from column model
	 */
	,reconfigure:function() {

		// {{{
		// remove old items
		var menu = this.menu;
		menu.removeAll();

		// add Select All item plus separator
		if(this.showSelectAll && 'radio' !== this.menuStyle) {
			menu.add(new Ext.menu.CheckItem({
				 text:this.selectAllText
				,checked:!(this.checkIndexes instanceof Array)
				,hideOnClick:false
				,handler:function(item) {
					var checked = ! item.checked;
					item.parentMenu.items.each(function(i) {
						if(item !== i && i.setChecked && !i.disabled) {
							i.setChecked(checked);
						}
					});
				}
			}),'-');
		}

		// }}}
		// {{{
		// add new items
		var cm = this.grid.colModel;
		var group = undefined;
		if('radio' === this.menuStyle) {
			group = 'g' + (new Date).getTime();	
		}
		Ext.each(cm.config, function(config) {
			var disable = false;
			if(config.header && config.dataIndex) {
				Ext.each(this.disableIndexes, function(item) {
					disable = disable ? disable : item === config.dataIndex;
				});
				if(!disable) {
					menu.add(new Ext.menu.CheckItem({
						 text:config.header
						,hideOnClick:false
						,group:group
						,checked:'all' === this.checkIndexes
						,dataIndex:config.dataIndex
					}));
				}
			}
		}, this);
		// }}}
		// {{{
		// check items
		if(this.checkIndexes instanceof Array) {
			Ext.each(this.checkIndexes, function(di) {
				var item = menu.items.find(function(itm) {
					return itm.dataIndex === di;
				});
				if(item) {
					item.setChecked(true, true);
				}
			}, this);
		}
		// }}}
		// {{{
		// disable items
		if(this.readonlyIndexes instanceof Array) {
			Ext.each(this.readonlyIndexes, function(di) {
				var item = menu.items.find(function(itm) {
					return itm.dataIndex === di;
				});
				if(item) {
					item.disable();
				}
			}, this);
		}
		// }}}

	} // eo function reconfigure
	// }}}

}); // eo extend

// eof
