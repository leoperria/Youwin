Application.pubblicazioni.WinUpload = function(){

  this.init();  
};
Ext.extend(Application.pubblicazioni.WinUpload , Ext.util.Observable, {

  win:null,
  id:null,
  formPanel:null,
  store:null,
  undo:false,
  
  init: function(){
    this.buildForm();
    this.addEvents({"updated" : true});    

    
    this.win = new Ext.Window({
        title: 'Aggiungi file',
        width: 500,
        height: 200,
        layout: 'border',
        plain:true,
        modal:false,
        closable:false,
        iconCls: 'icon-shield',
        items:[this.formPanel],
        buttons: [{
          text: 'Salva',
          handler:function(){
        	this.uploadForm();
          },
          scope:this
        },{
          text: 'Annulla',
          handler:function(){
            this.hide();
          },
          scope:this
        }]
      });
      
      
  },
 
  show: function(id){
    this.id=id;
    this.win.show();
  },

  
  uploadForm:function(){
	if(this.formPanel.getForm().isValid()){
	  this.formPanel.getForm().submit({
	    url: 'pubblicazioni/addfile',
	    params:{
		 ID_pubblicazione:this.id  
	    },
	    waitMsg: 'Upload in corso attendere...',
	    success: function(fp, o){
	      this.fireEvent('updated');
	      this.hide();
	    },
	    scope:this
	  });
    }
  },

  hide: function(){
    Ext.QuickTips.init();
    this.win.close();
  },
  
  buildForm:function(){
    this.formPanel= new Ext.FormPanel({
      fileUpload: true,
      width: 500,
      region:'center',
      baseCls: 'x-plain',
      autoHeight: true,
      bodyStyle: 'padding: 10px 10px 0 10px;',
      labelWidth: 100,
      defaults: {anchor: '95%',allowBlank: false,msgTarget: 'side'},
      items: [{
        xtype: 'textfield',
        fieldLabel: 'Descrizione',
        name:'description'
      },{
       xtype: 'fileuploadfield',
       id: 'form-file',
       emptyText: 'Seleziona il file',
       fieldLabel: 'File',
       name: 'file',
       buttonText: '',
       buttonCfg: {
         iconCls: 'upload-icon'
       }
      }]
    });
  }
});