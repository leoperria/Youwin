Concorsi.concorsi.WinBuildCalendar = function(){
  this.init();
};

Ext.extend(Concorsi.concorsi.WinBuildCalendar  , Ext.util.Observable, {

  win:null,
  formPanel:null,
  cb:null,

  init: function(){

    this.addEvents({
      "updated" : true
    });

    this.cb=new Ext.form.Checkbox({
        fieldLabel: 'Cancella soltanto',
        name:'nonricreare'
    });

    this.formPanel= new Ext.FormPanel({
      baseCls: 'x-plain',
      bodyStyle: 'padding: 10px 10px 0 10px;',
      labelWidth: 100,
      defaults: {
        anchor:'90%',
        msgTarget:'side'
      },
      plugins: [new Ext.ux.OOSubmit()],
      items:[this.cb]
    });

    this.win = new Ext.Window({
      title: 'Ricrea calendario',
      iconCls: 'icon-lighting',
      width: 300,
      height: 180,
      plain:true,
      modal:true,
      border:false,
      constrainHeader:true,
      shim:false,
      animCollapse:false,
      buttonAlign:'right',
      maximizable:false,
      items:[this.formPanel],
      buttons: [{
        text:'Esegui',
        handler:this.saveForm,
        scope:this
      }]
    });

  },

  show: function(){
    this.win.show();
  },

   hide: function(){
    this.win.close();
  },

  saveForm:function(){

    Ext.Msg.show({
      title:'Calendario',
      msg:"Creare il calendario ? <br/> ATTENZIONE: Il calendario attuale verr&agrave; <br/>cancellato definitivamente!",
      buttons: Ext.Msg.YESNO,
      fn:function(btn){
        if (btn=='yes'){
          Ext.Ajax.request({
            url:"controlcenter/crea-calendario",
            params:{nonricreare:this.cb.getValue()},
            success:function(response,options){
              var result=Ext.decode(response.responseText);
              if (result.success==true){
                Ext.MessageBox.show({title: 'Calendario', msg: 'OK. Il calendario creato correttamente!', buttons: Ext.MessageBox.OK, icon: Ext.MessageBox.INFO});
                this.hide();
              }else{
                if (result.errorMessages){
                  var errMsg=result.errorMessages.join("<br/>");
                  Ext.MessageBox.show({title: 'Problema...', msg: errMsg,  buttons: Ext.MessageBox.OK, icon: Ext.MessageBox.WARNING});
                }
              }
            },
            scope:this
          });
        }
      },
      icon: Ext.MessageBox.QUESTION,
      scope:this
    });

  }

});
