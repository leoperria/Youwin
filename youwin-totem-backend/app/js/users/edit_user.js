Application.users.WinEdit = function(id,orgid){
  var h=250;
  var sparams={id:id};
  var lparams={id:id};
  if(UserLevel.isDeveloper()){
	  h=300;
	  sparams={id:id,orgid:orgid};
	  lparams={id:id,orgid:orgid};
  }
  this.init({
    winConfig:{
      title:'Modifica/aggiungi utente',
      height:h
    },
    loadUrl:'users/get',
    loadParams:lparams,
    saveUrl:'users/save',
    saveParams:sparams
  });
  
};

Ext.extend(Application.users.WinEdit , Application.api.GenericForm, {
  
  getFormItems: function(){
	var formItems=new Array();
	this.titoloField=new Ext.form.TextField({
        fieldLabel: 'Titolo',
        name:'titolo'
    });
    
	this.nameField=new Ext.form.TextField({
        fieldLabel: 'Nome',
        name:'nome'
    });
    
    this.cognomeField=new Ext.form.TextField({
      fieldLabel:'Cognome',
      name:'cognome'
    });
    
    this.nicknameField=new Ext.form.TextField({
      fieldLabel:'Userid',
      name:'user',
      allowBlank:false
    });
    
    this.activeField=new Ext.form.Checkbox({
      fieldLabel:'Utente attivo',
      name:'active'
    });
    
    this.superuserField=new Ext.form.Checkbox({
        fieldLabel:'Superuser',
        name:'superuser'
    });
    
    this.developerField=new Ext.form.Checkbox({
        fieldLabel:'Developer',
        name:'developer'
    });
    formItems.push(this.titoloField,
		  this.nameField,
		  this.cognomeField,
		  this.nicknameField,
		  this.activeField);
    if(UserLevel.isDeveloper()){
      formItems.push(this.superuserField,this.developerField);
    }
    return formItems;
    
  },
  
  newRecordInit:function(){}
  
});