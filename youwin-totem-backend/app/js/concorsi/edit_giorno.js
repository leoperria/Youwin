Concorsi.concorsi.WinEditGiorno = function(id){
  this.init({
    winConfig:{
      title:'Modifica/aggiungi giorno',
      height:300
    },
    loadUrl:'concorsi/get-giorno',
    loadParams:{id:id},
    saveUrl:'concorsi/save-giorno',
    saveParams:{id:id}
  });

};

Ext.extend(Concorsi.concorsi.WinEditGiorno , Application.api.GenericForm, {

  getFormItems: function(){
	return [
      new Ext.form.DateField({
        fieldLabel: 'Data',
        name:'data'
      }),
      new Ext.form.TextField({
        fieldLabel: 'Start',
        name:'ora_start'
      }),
      new Ext.form.TextField({
        fieldLabel:'Stop',
        name:'ora_stop'
      })
    ];
  },

  newRecordInit:function(){}

});