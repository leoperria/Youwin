Ext.namespace("Application.organizations");
Application.organizations.WinOrganizations = function(){
  this.init({
    winConfig:{title:'Gestione Enti',width:920},
    deleteUrl:'organizations/delete'
  });
};

Ext.extend(Application.organizations.WinOrganizations, Application.apiGrid.WinList, {

  getStoreConfig:function(){
    return new Ext.data.JsonStore({
      url: 'organizations/list',
      root:'results',
      totalProperty:'totalCount',
      id:'orgid',
      fields:[
        {name:'orgid',type:'int'},
        {name:'data_iscrizione',type:'date',dateFormat:'Y-m-d H:i:s'},
        {name:'ID_tipologia_ente',type:'int'},
        {name:'ID_file_simbolo',type:'int'},
        {name:'N_ultimo_protocollo',type:'int'},
        {name:'tipo_ente'},
        {name:'denominazione'},
        {name:'localita'},
        {name:'ID_provincia'},
        {name:'cap'},
        {name:'indirizzo'},
        {name:'codfis'},
        {name:'piva'},
        {name:'tel'},
        {name:'fax'}
      ]
    });
  },
  
  getColumnConfig:function(){
	function indirizzoRender(value,metadata,record,rowIndex,colIndex,store){
	  var ind="";
	  if(typeof record.data.indirizzo!='undefined'){ind=record.data.indirizzo;}
	  if(typeof record.data.cap!='undefined'){ind=ind+" "+record.data.cap;}
	  if(typeof record.data.localita!='undefined'){ind=ind+" "+record.data.localita;}
	  if(typeof record.data.ID_provincia!='undefined'){ind=ind+" ("+record.data.ID_provincia+")";}
	  return ind;
	}
	
	function telRender(value,metadata,record,rowIndex,colIndex,store){
	  var recapito="";
	  if(typeof record.data.tel!='undefined'){recapito=record.data.tel;}
	  if(typeof record.data.fax!='undefined'){recapito=recapito+" / "+record.data.fax;}
	  return recapito;
	}
    return [
        { header:'Data iscrizione', width:90, dataIndex:'data_iscrizione',renderer:Utils.utils.dateRenderer},
        { header:'Tipo ente', width:120, dataIndex:'tipo_ente'},
        { header:'Denominazione', width:150, dataIndex:'denominazione'},
        { header:'Indirizzo',width:200,dataIndex:'indirizzo',renderer:indirizzoRender},
        { header:'P. iva', width:90, dataIndex:'piva'},
        { header:'Cod. Fis.', width:90, dataIndex:'codfis'},
        { header:'Tel. / Fax', width:140, dataIndex:'tel',renderer:telRender}
      ];
  },
  
  edit:function(id){
    this.editWin=new Application.organizations.WinEdit();
    this.editWin.show(id);
    this.editWin.on('updated',function(){this.refreshPanel();},this);
  }
});