Ext.namespace("UserLevel");
UserLevel= function(){
	var developer;
	var superuser;
  return{
  	init:function(){},
  	setObject:function(d,s){developer=d;superuser=s;},
    isDeveloper:function(){return developer;},
    isSuperuser:function(){return superuser;},
    getObject:function(){return this;}
  };
}();

Ext.onReady(UserLevel.init, UserLevel, true);

Ext.namespace("Identity");

Identity.info= function(){
  var infoUtente=new Array();
  
  return{
  	init:function(){
    
    },
    setInfoUtente : function(arr){
      infoUtente=arr;
    },
    
    getInfoUtente:function(){
      return infoUtente;
    }
    
  };
}();
Ext.onReady(Identity.info.init, Identity.info, true);
