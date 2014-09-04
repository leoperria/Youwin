Concorsi.concorsi.WinControlCenter = function(){
 
};

Ext.extend(Concorsi.concorsi.WinControlCenter , Ext.util.Observable, {

  win:null,
  gridPanel:null,
  store:null,
  rowactions:null,
  premiStore:null,

  /************************************** INIT *************************************/


  show: function(){
        
    this.premiStore=  new Ext.data.JsonStore({
      url: 'concorsi/list-premi',
      root:'results',
      totalProperty:'totalCount',
      autoLoad:true,
      id:'ID',
      fields:[
        {name:'ID',type:'int'},
        {name:'codice'},
        {name:'ID_concorso'},
        {name:'articolo'},
        {name:'denominazione'},
        {name:'qnt_totale',type:'int'},
        {name:'valore',type:'float'},
        {name:'importo',type:'float'}
      ]
    });
    this.premiStore.on("load",function(){
      this.init();
      this.win.show();
      this.refreshPanel();
    },this);
 
  },


  init: function(){

    var i;
    var r;
    
    var fields=[
      {name:'ID',type:'int'},
      {name:'data',type:'date',dateFormat:'Y-m-d'},
      {name:'giorno_settimana',type:'int'}
    ];
    for( i=0;i<this.premiStore.getCount();i++){
      r=this.premiStore.getAt(i);
      fields.push(
        {name:'Q_'+r.data.ID,type:'int'}
      );
    }

    this.store= new Ext.data.JsonStore({
      url: 'controlcenter/list-calendario',
      root:'results',
      totalProperty:'totalCount',
      id:'ID',
      fields:fields
    });


    var columns=[
      {header:'Giorno',width:120,dataIndex:'giorno_settimana',renderer:function(value,metadata,record,rowIndex,colIndex,store){
        var g=Utils.utils.getDayOfWeek(value-1);
        var style="";
        if (value==6){
          style='style="color:blue"';
        }else if (value==7){
          style='style="color:red"';
        }
        return  '<span '+style+'>'+g+'</span>';
      }},
      { header:'Data', width:120, dataIndex:'data',renderer:Utils.utils.dateRenderer},
    ];

    for( i=0;i<this.premiStore.getCount();i++){
      r=this.premiStore.getAt(i);
      columns.push(
        {header:r.data.codice,dataIndex:'Q_'+r.data.ID}
      );
    }

    this.gridPanel=new Ext.grid.GridPanel({
      region:'center',
      store:this.store,
      columns:columns,
      tbar:this.buildMenu()
    });

    this.win = new Ext.Window({
      title: 'Control Center',
      iconCls: 'icon-cog',
      width: 800,
      height: 500,
      layout: 'border',
      plain:true,
      modal:false,
      border:false,
      constrainHeader:true,
      shim:false,
      animCollapse:false,
      buttonAlign:'right',
      closeAction:'hide',
      maximizable:true,
      items:[this.gridPanel],
      buttons:[{
        text:'Chiudi',
        handler:this.hide,
        scope:this
      }]
    });
  },



  hide: function(){
    Ext.QuickTips.init();
    this.win.close();
  },


  buildMenu:function(){
    var menuItems=new Array();
    menuItems.push({
      text:'Aggiungi',
      icon:'css/icons/add.png',
      iconCls:'x-btn-text-icon',
      tooltip:{
        title:'Aggiungi',
        text:'Aggiungi'
      },
      handler: function(){
        this.edit('new');
      },
      scope:this
    },'-',{
      text:'Modifica',
      icon:'css/icons/pencil.png',
      iconCls:'x-btn-text-icon',
      tooltip:{
        title:'Modifica',
        text:'Modifica'
      },
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
      tooltip:{
        title:'Elimina',
        text:'Elimina'
      },
      handler:function(){
        if(!this.gridPanel.getSelectionModel().getSelected()){
          Ext.Msg.alert("Attenzione","Selezionare una riga della lista");
          return;
        }
        this.deleteRecord(this.gridPanel.getSelectionModel().getSelected().id);
      },
      scope:this
    });
    return menuItems;
  },

  refreshPanel:function(){
    this.gridPanel.getStore().reload();
  },

  edit:function(id){
    alert('Edit id='+id);
    return null;
  }

})