Ext.namespace("Application.pubblicazioni");
Application.pubblicazioni.WinPubblicazioni = function(){
  this.rowAction=new Ext.ux.grid.RowActions({
      actions:[{
        iconCls:'icon-edit',
        qtip:'Modifica'
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
      }
    },this);
    
  this.init({
    winConfig:{title:'Gestione pubblicazioni',width:950},
    deleteUrl:'pubblicazioni/delete',
    gridConfig:{plugins:[this.rowAction],stripeRows:true},
    gridButtons:['-',{
        text:'Ricerca',
        icon:'css/icons/magnifier.png',
        iconCls:'x-btn-text-icon',
        tooltip:{title:'Ricerca',text:'Permette di utilizzare dei parametri per effettuare una ricerca tra le pubblicazioni'},
        handler:function(){
          this.search();
        },
        scope:this
      }
    ]
  });
};

Ext.extend(Application.pubblicazioni.WinPubblicazioni, Application.apiGrid.WinList, {

  getStoreConfig:function(){

    return new Ext.data.JsonStore({
      url: 'pubblicazioni/list',
      root:'results',
      totalProperty:'totalCount',
      id:'ID_pubblicazione',
      fields:[
        {name:'ID_pubblicazione',type:'int'},
        {name:'orgid',type:'int'},
        {name:'ID_user',type:'int'},
        {name:'ID_mittente',type:'int'},
        {name:'ID_categoria',type:'int'},
        {name:'data_inserimento',type:'date',dateFormat:'Y-m-d H:i:s'},
        {name:'pubblicato_dal',type:'date',dateFormat:'Y-m-d H:i:s'},
        {name:'pubblicato_al',type:'date',dateFormat:'Y-m-d H:i:s'},
        {name:'autore'},
        {name:'articolo'},
        {name:'n_protocollo'},
        {name:'anno_protocollo',type:'int'},
        {name:'oggetto'},
        {name:'note'},
        {name:'categoria'},
        {name:'mittente'},
        {name:'files'},
      ]
    });
  },
  
  getColumnConfig:function(){
    function protocolloRender(value,metadata,record,rowIndex,colIndex,store){
    	return record.data.n_protocollo+"/"+record.data.anno_protocollo;
    }
    
    function periodoRender(value,metadata,record,rowIndex,colIndex,store){
      if(Ext.isDate(value)){return value.format('d/m/Y');}
      return value;
    }

    return [
        { header:'Categoria', width:100, dataIndex:'categoria'},
        { header:'Protocollo', width:80, dataIndex:'n_protocollo',renderer:protocolloRender},
        { header:'Data ins.', width:75, dataIndex:'data_inserimento',renderer:Utils.utils.dateRenderer},
        { header:'Mittente', width:100, dataIndex:'mittente'},
        { header:'Oggetto', width:220, dataIndex:'oggetto'},
        { header:'Autore', width:90, dataIndex:'autore'},
        { header:'Pubblica dal', width:75, dataIndex:'pubblicato_dal',renderer:periodoRender},
        { header:'Pubblica al', width:75, dataIndex:'pubblicato_al',renderer:periodoRender},
        { header:'Allegati',width:55, dataIndex:'files'},
        this.rowAction
      ];
  },
  
  edit:function(id){
    this.editWin=new Application.pubblicazioni.WinEdit();
    this.editWin.show(id);
    this.editWin.on('updated',function(){this.refreshPanel();},this);
  },
  
  search:function(){
	this.searchWin=new Application.pubblicazioni.searchMask();
	this.searchWin.show();
	this.searchWin.on('searchPubblicazione',function(){
	  this.criteria=new Array();
	  this.criteria['data_inserimento']=(!this.searchWin.dataInserimentoField.getValue())? '':this.searchWin.dataInserimentoField.getValue().format('d/m/Y');
	  this.criteria['pubblicato_dal']=(!this.searchWin.pubDalField.getValue())? '':this.searchWin.pubDalField.getValue().format('d/m/Y');
	  this.criteria['pubblicato_al']=(!this.searchWin.pubAlField.getValue())? '':this.searchWin.pubAlField.getValue().format('d/m/Y');
	  this.criteria['oggetto']=(!this.searchWin.oggettoField.getValue())? '':this.searchWin.oggettoField.getValue();
	  this.criteria['autore']=(!this.searchWin.autoreField.getValue())? '':this.searchWin.autoreField.getValue();
	  this.criteria['n_protocollo']=(!this.searchWin.nProtocolloField.getValue())? 0:this.searchWin.nProtocolloField.getValue();
	  this.criteria['anno_protocollo']=(!this.searchWin.annoProtocolloField.getValue())? 0:this.searchWin.annoProtocolloField.getValue();
	  this.criteria['ID_categoria']=(!this.searchWin.comboCategorie.getValue())? 0:this.searchWin.comboCategorie.getValue();
	  this.criteria['ID_mittente']=(!this.searchWin.comboMittenti.getValue())? 0:this.searchWin.comboMittenti.getValue();
	  this.criteria['file_name']=(!this.searchWin.nomeFile.getValue())? '':this.searchWin.nomeFile.getValue();
	  this.gridPanel.getStore().load({
	    params:{
		  data_inserimento:this.criteria['data_inserimento'],
		  pubblicato_dal:this.criteria['pubblicato_dal'],
		  pubblicato_al:this.criteria['pubblicato_al'],
		  oggetto:this.criteria['oggetto'],
		  autore:this.criteria['autore'],
		  n_protocollo:this.criteria['n_protocollo'],
		  anno_protocollo:this.criteria['anno_protocollo'],
		  ID_categoria:this.criteria['ID_categoria'],
		  ID_mittente:this.criteria['ID_mittente'],
		  file_name:this.criteria['file_name'],
		  start:0,
	      limit:this.bbar.pageSize    
	      }
	    });
	},this);
  }
});