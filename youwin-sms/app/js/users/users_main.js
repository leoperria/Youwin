Ext.namespace("Application.users");
Application.users.WinUsers = function(){
  this.rowAction=new Ext.ux.grid.RowActions({
      actions:[{
        iconCls:'icon-edit',
        qtip:'Modifica'
      },{
        iconCls:'icon-key',
        qtip:'Modifica password'
      },{
        iconCls:'icon-delete',
        qtip:'Elimina'
      }],
        widthIntercept:Ext.isSafari ? 4 : 2,
        id:'actions'
    });
    
    this.rowAction.on('action',function(grid, record, action, row, col){
      switch(action) {
        case 'icon-edit':
          console.log(record.id);
          this.edit(record.id);
        break;

		case 'icon-delete':
		  this.deleteRecord(record.id);
		break;
        
        case 'icon-key':
          var editPasswordWin=new Application.users.WinEditPassword();
          editPasswordWin.show(record.id);
        break;
      }
    },this);
    
  this.init({
    winConfig:{title:'Gestione utenti',width:800},
    deleteUrl:'users/delete',
    gridConfig:{plugins:[this.rowAction]},
    stopRefreshOnLoad:UserLevel.isDeveloper()
  });
};

Ext.extend(Application.users.WinUsers, Application.apiGrid.WinList, {

  getStoreConfig:function(){

    return new Ext.data.JsonStore({
      url: 'users/list',
      root:'results',
      totalProperty:'totalCount',
      id:'ID_user',
      fields:[
        {name:'ID_user',type:'int'},
        {name:'nome'},
        {name:'cognome'},
        {name:'titolo'},
        {name:'user'},
        {name:'active',type:'int'}
      ]
    });
  },
  
  buildMenu:function(){
    var mItems=new Array();
    mItems.push({
        text:'Aggiungi',
        icon:'css/icons/add.png',
        iconCls:'x-btn-text-icon',
        tooltip:{title:'Aggiungi',text:'Aggiungi'},
        handler: function(){
          this.edit('new');
        },
        scope:this
      },'-',{
        text:'Modifica',
        icon:'css/icons/pencil.png',
        iconCls:'x-btn-text-icon',
        tooltip:{title:'Modifica',text:'Modifica'},
        handler:function(){
          if(!this.gridPanel.getSelectionModel().getSelected()){
            Ext.Msg.alert("Attenzione","Selezionare una riga della lista");
            return;
          }
          this.edit(this.gridPanel.getSelectionModel().getSelected().id);
        },
        scope:this
      },'-',{
        text:'Elimina',
        icon:'css/icons/delete.png',
        iconCls:'x-btn-text-icon',
        tooltip:{title:'Elimina',text:'Elimina'},
        handler:function(){
          if(!this.gridPanel.getSelectionModel().getSelected()){
            Ext.Msg.alert("Attenzione","Selezionare una riga della lista");
            return;
          }
          this.deleteRecord(this.gridPanel.getSelectionModel().getSelected().id);
        },
        scope:this
      });
    if(UserLevel.isDeveloper()){
      var organizationsStore=new Ext.data.JsonStore({
          url:'organizations/list',
          root:'results',
          fields:[
            {name:'orgid',type:'int'},
            {name:'denominazione'}
          ]
        });
      this.comboOrg=new Ext.form.ComboBox({
        name:'orgid',
        fieldLabel:'Ente',
        store:organizationsStore,
        triggerAction:'all',
        forceSelection:true,
        width:300,
        displayField:'denominazione',
        valueField:'orgid',
        hiddenName:'orgid',
        emptyText:'Selezionare un\'ente'
      });
      this.comboOrg.on('select',function(){
        this.gridPanel.getStore().load({
           params:{
             start:0,
             orgid:this.comboOrg.getValue(),
             limit:this.bbar.pageSize      
            }
        });
      },this);
      mItems.push('Ente:',this.comboOrg);
    }
    return mItems;
  },
  
  getColumnConfig:function(){
    
    return [
        { header:'Titolo', width:120, dataIndex:'titolo'},
        { header:'Nome', width:120, dataIndex:'nome'},
        { header:'Cognome', width:100, dataIndex:'cognome'},
        { header:'Userid', width:100, dataIndex:'user'},
        { header:'Attivo',align:'center',width:50, dataIndex:'active',renderer:function(v){return  v==1 ? "Si":"No";}},
        this.rowAction
      ];
  },
  
  edit:function(id){
	var orgid=false;
	if(UserLevel.isDeveloper()){
	 if(this.comboOrg.getValue()==''){
	  Ext.Msg.alert("Attenzione","Selezionare un'ente dalla combobox");
	  return;
	 }
	 orgid=this.comboOrg.getValue();
	}
    this.editWin=new Application.users.WinEdit(id,orgid);
    this.editWin.show(id);
    this.editWin.on('updated',function(){this.refreshPanel();},this);
  }
});