Concorsi.concorsi.WinCreaIntervallo = function(){
  this.init();
};

Ext.extend(Concorsi.concorsi.WinCreaIntervallo  , Ext.util.Observable, {

  win:null,
  formPanel:null,

  init: function(){

    this.addEvents({
      "updated" : true
    });

    var items=[
      new Ext.form.DateField({
        fieldLabel: 'Data iniziale',
        name:'data_start'
      }),
      new Ext.form.DateField({
        fieldLabel: 'Data finale',
        name:'data_stop'
      }),
      new Ext.form.TextField({
        fieldLabel: 'Ora start',
        value:'00:00:00',
        name:'ora_start'
      }),
      new Ext.form.TextField({
        fieldLabel:'Ora stop',
        value:'23:59:59',
        name:'ora_stop'
      })
    ];

    this.formPanel= new Ext.FormPanel({
      baseCls: 'x-plain',
      bodyStyle: 'padding: 10px 10px 0 10px;',
      labelWidth: 100,
      defaults: {
        anchor:'90%',
        msgTarget:'side'
      },
      plugins: [new Ext.ux.OOSubmit()],
      items:items
    });

    this.win = new Ext.Window({
      title: 'Crea intervallo di date',
      iconCls: 'icon-date',
      width: 300,
      height: 200,
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
        text:'Crea',
        handler:this.saveForm,
        scope:this
      }]
    });

  },

  show: function(){
    this.win.show();
  },

   hide: function(){
   // Ext.QuickTips.init();
    this.win.close();
  },

  saveForm:function(){

    if(this.formPanel.getForm().isValid()){
      this.formPanel.getForm().submit({
        url:"concorsi/crea-intervallo",
        waitMsg: 'Creazione in corso...',
        success: function(form,action){
          if(action.result.message){
            Ext.Msg.alert('Messaggio',action.result.message);
          }
          this.fireEvent('updated');
          this.hide();
        },
        failure: function(form,action){
          if (action.result.errorMessages){
            var errMsg=action.result.errorMessages.join("<br/>");
            Ext.MessageBox.show({
              title: 'Problema...',
              msg: errMsg,
              buttons: Ext.MessageBox.OK,
              icon: Ext.MessageBox.WARNING,
              fn:function(btn){
                if (action.result.closeAfterErrors){
                  this.hide();
                }
              },
              scope:this
            });
          }

        },
        scope:this
      });
    }
  }

});
