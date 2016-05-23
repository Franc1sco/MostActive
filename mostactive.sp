#pragma semicolon 1
#include <sourcemod>
#include <sdktools>

#define IDAYS 26


#define VERSION "2.0"

new JugadoEspectador[MAXPLAYERS+1] = 0;
new JugadoT[MAXPLAYERS+1] = 0;
new JugadoCT[MAXPLAYERS+1] = 0;

new String:g_sCmdLogPath[256];
char sql_buffer[3096];

bool ismysql;

// DB handle
new Handle:db = INVALID_HANDLE;

public Plugin:myinfo = 
{
	name = "SM Most Active",
	author = "Franc1sco Steam: franug",
	description = "A rank based in time played",
	version = VERSION,
	url = "http://steamcommunity.com/id/franug"
};

public OnPluginStart()
{
	CreateConVar("sm_mostactive_version", VERSION, "version", FCVAR_PLUGIN|FCVAR_SPONLY|FCVAR_REPLICATED|FCVAR_NOTIFY);
	RegConsoleCmd("sm_active", DOMenu);

 	for(new i=0;;i++)
	{
		BuildPath(Path_SM, g_sCmdLogPath, sizeof(g_sCmdLogPath), "logs/mostactive_%d.log", i);
		if ( !FileExists(g_sCmdLogPath) )
			break;
	}
	
	SQL_TConnect(OnSqlConnect, "mostactive");
}

public OnSqlConnect(Handle:owner, Handle:hndl, const String:error[], any:data)
{
	if (hndl == INVALID_HANDLE)
	{
		LogError("Database failure: %s", error);
		
		SetFailState("Databases dont work");
	}
	else
	{
		db = hndl;
		
		SQL_GetDriverIdent(SQL_ReadDriver(db), sql_buffer, sizeof(sql_buffer));
		ismysql = StrEqual(sql_buffer,"mysql", false) ? true : false;
	
		if (ismysql)
		{
			Format(sql_buffer, sizeof(sql_buffer), "CREATE TABLE IF NOT EXISTS `mostactive` (`playername` varchar(128) NOT NULL, `steamid` varchar(32) PRIMARY KEY NOT NULL,`last_accountuse` int(64) NOT NULL, `timeCT` INT( 16 ), `timeTT` INT( 16 ),`timeSPE` INT( 16 ), `total` INT( 16 ))");

			SQL_TQuery(db, tbasicoC, sql_buffer);
			LogToFileEx(g_sCmdLogPath, "Query %s", sql_buffer);

		}
		else
		{
			Format(sql_buffer, sizeof(sql_buffer), "CREATE TABLE IF NOT EXISTS mostactive (playername varchar(128) NOT NULL, steamid varchar(32) PRIMARY KEY NOT NULL,last_accountuse int(64) NOT NULL, timeCT INTEGER, timeTT INTEGER, timeSPE INTEGER, total INTEGER)");
		
			SQL_TQuery(db, tbasicoC, sql_buffer);
			LogToFileEx(g_sCmdLogPath, "Query %s", sql_buffer);
		}
		
		PruneDatabase();
	}
}

public tbasicoC(Handle:owner, Handle:hndl, const String:error[], any:data)
{
	if (hndl == INVALID_HANDLE)
	{
		LogError("Query failure: %s", error);
		return;
	}
	
	for(new client = 1; client <= MaxClients; client++)
	{
		if(IsClientInGame(client))
		{
			OnClientPostAdminCheck(client);
		}
	}
}

public tbasico(Handle:owner, Handle:hndl, const String:error[], any:data)
{
	if (hndl == INVALID_HANDLE)
	{
		LogError("Query failure: %s", error);
	}
}

Nuevo(client)
{
	decl String:query[255], String:steamid[32];
	GetClientAuthId(client, AuthId_Steam2,  steamid, sizeof(steamid) );
	new userid = GetClientUserId(client);
	
	new String:Name[MAX_NAME_LENGTH+1];
	new String:SafeName[(sizeof(Name)*2)+1];
	if (!GetClientName(client, Name, sizeof(Name)))
		Format(SafeName, sizeof(SafeName), "<noname>");
	else
	{
		TrimString(Name);
		SQL_EscapeString(db, Name, SafeName, sizeof(SafeName));
	}
		
	Format(query, sizeof(query), "INSERT INTO mostactive(playername, steamid, last_accountuse, timeCT, timeTT, timeSPE, total) VALUES('%s', '%s', '%d', '0', '0', '0', '0');", SafeName, steamid, GetTime());
	SQL_TQuery(db, tbasico, query, userid);
	LogToFileEx(g_sCmdLogPath, "Query %s", query);
	JugadoCT[client] = 0;
	JugadoT[client] = 0;
	JugadoEspectador[client] = 0;
	
}

CheckSteamID(client)
{
	decl String:query[255], String:steamid[32];
	GetClientAuthId(client, AuthId_Steam2,  steamid, sizeof(steamid) );
	
	Format(query, sizeof(query), "SELECT timeCT, timeTT, timeSPE FROM mostactive WHERE steamid = '%s'", steamid);
	SQL_TQuery(db, T_CheckSteamID, query, GetClientUserId(client));
	LogToFileEx(g_sCmdLogPath, "Query %s", query);
}

public T_CheckSteamID(Handle:owner, Handle:hndl, const String:error[], any:data)
{
	new client;
 
	/* Make sure the client didn't disconnect while the thread was running */
	if ((client = GetClientOfUserId(data)) == 0)
	{
		return;
	}
	
	if (hndl == INVALID_HANDLE)
	{
		LogError("Query failure: %s", error);
		return;
	}
	if (!SQL_GetRowCount(hndl) || !SQL_FetchRow(hndl)) 
	{
		Nuevo(client);
		return;
	}
	
	JugadoCT[client] = SQL_FetchInt(hndl, 0);
	JugadoT[client] = SQL_FetchInt(hndl, 1);
	JugadoEspectador[client] = SQL_FetchInt(hndl, 2);
}

SaveCookies(client)
{
	decl String:steamid[32];
	GetClientAuthId(client, AuthId_Steam2,  steamid, sizeof(steamid) );
	new String:Name[MAX_NAME_LENGTH+1];
	new String:SafeName[(sizeof(Name)*2)+1];
	if (!GetClientName(client, Name, sizeof(Name)))
		Format(SafeName, sizeof(SafeName), "<noname>");
	else
	{
		TrimString(Name);
		SQL_EscapeString(db, Name, SafeName, sizeof(SafeName));
	}	

	decl String:buffer[3096];
	Format(buffer, sizeof(buffer), "UPDATE mostactive SET last_accountuse = %d, playername = '%s',timeCT = '%i',timeTT = '%i', timeSPE = '%i',total = '%i' WHERE steamid = '%s';",GetTime(), SafeName, JugadoCT[client],JugadoT[client],JugadoEspectador[client],JugadoCT[client]+JugadoT[client]+JugadoEspectador[client], steamid);
	SQL_TQuery(db, tbasico, buffer);
	LogToFileEx(g_sCmdLogPath, "Query %s", buffer);
}

public OnPluginEnd()
{
	for(new client = 1; client <= MaxClients; client++)
	{
		if(IsClientInGame(client))
		{
			OnClientDisconnect(client);
		}
	}
}

public OnClientDisconnect(client)
{
	if(!IsFakeClient(client)) SaveCookies(client);
}

public OnClientPostAdminCheck(client)
{
	if(!IsFakeClient(client)) CheckSteamID(client);
}

public PruneDatabase()
{
	if (db == INVALID_HANDLE)
	{
		LogToFileEx(g_sCmdLogPath, "Prune Database: No connection");
		return;
	}

	new maxlastaccuse;
	maxlastaccuse = GetTime() - (IDAYS * 86400);

	decl String:buffer[1024];

	if (ismysql)
		Format(buffer, sizeof(buffer), "DELETE FROM `mostactive` WHERE `last_accountuse`<'%d' AND `last_accountuse`>'0';", maxlastaccuse);
	else
		Format(buffer, sizeof(buffer), "DELETE FROM mostactive WHERE last_accountuse<'%d' AND last_accountuse>'0';", maxlastaccuse);

	LogToFileEx(g_sCmdLogPath, "Query %s", buffer);
	SQL_TQuery(db, tbasicoP, buffer);
}

public tbasicoP(Handle:owner, Handle:hndl, const String:error[], any:data)
{
	if (hndl == INVALID_HANDLE)
	{
		LogToFileEx(g_sCmdLogPath, "Query failure: %s", error);
	}
	//LogMessage("Prune Database successful");
}

public OnMapStart()
{
	CreateTimer(1.0, Temporizador, _, TIMER_REPEAT|TIMER_FLAG_NO_MAPCHANGE);
}

public Action:Temporizador(Handle:timer)
{
	for(new i = 1; i <= MaxClients; i++) 
	{
		if(IsClientInGame(i))
		{
	           new team = GetClientTeam(i);

                   if(team == 2)
	           {
                      ++JugadoT[i];

                   }
                   else if(team == 3)
                   {
                      ++JugadoCT[i];  
                   }
                   else  
                   {
                      ++JugadoEspectador[i];
	           }
		} 
	}
}


public showTOP(client){

	if (db != INVALID_HANDLE)
	{
		decl String:buffer[200];
		Format(buffer, sizeof(buffer), "SELECT playername, total FROM mostactive ORDER BY total DESC LIMIT 999");
		SQL_TQuery(db, SQLTopShow, buffer, client);
	} else {
		PrintToChat(client, "Rank System is now not avilable");
	}
}

public SQLTopShow(Handle:owner, Handle:hndl, const String:error[], any:client){

		if(hndl == INVALID_HANDLE)
		{
			LogError(error);
			PrintToServer("Last Connect SQL Error: %s", error);
			return;
		}

		new Handle:menu2 = CreateMenu(DIDMenuHandler2);
		SetMenuTitle(menu2, "Top Total played");


                new orden = 0;
                decl String:numero[64];
		decl String:name[64];
		decl String:texto[128];

		if (SQL_HasResultSet(hndl))
		{
			while (SQL_FetchRow(hndl))
			{
                                orden++;
                                Format(numero,64, "option%i", orden);
				SQL_FetchString(hndl, 0, name, sizeof(name));
	                        new Time = SQL_FetchInt(hndl,1);
	                        new Hours = (Time/60/60);
	                        new Minutes = (Time/60)%(60);
	                        new Seconds = (Time%60);

        
	
                                Format(texto,128, "n%i %s - %d h. %d m. %d s.", orden,name,Hours, Minutes, Seconds);    
    
                                AddMenuItem(menu2, numero, texto);


			}
		}

		if(orden < 1) 
		{
			AddMenuItem(menu2, "empty", "TOP is empty!");
		}
		
                SetMenuExitButton(menu2, true);
                DisplayMenu(menu2, client, MENU_TIME_FOREVER);

}

public showTOP2(client){

	if (db != INVALID_HANDLE)
	{
		decl String:buffer[200];
		Format(buffer, sizeof(buffer), "SELECT playername, timeSPE FROM mostactive ORDER BY timeSPE DESC LIMIT 999");
		SQL_TQuery(db, SQLTopShow2, buffer, client);
	} else {
		PrintToChat(client, "Rank System is now not avilable");
	}
}

public SQLTopShow2(Handle:owner, Handle:hndl, const String:error[], any:client){

		if(hndl == INVALID_HANDLE)
		{
			LogError(error);
			PrintToServer("Last Connect SQL Error: %s", error);
			return;
		}

		new Handle:menu2 = CreateMenu(DIDMenuHandler2);
		SetMenuTitle(menu2, "Top Spectator");


                new orden = 0;
                decl String:numero[64];
		decl String:name[64];
		decl String:texto[128];

		if (SQL_HasResultSet(hndl))
		{
			while (SQL_FetchRow(hndl))
			{
                                orden++;
                                Format(numero,64, "option%i", orden);
				SQL_FetchString(hndl, 0, name, sizeof(name));
	                        new Time = SQL_FetchInt(hndl,1);
	                        new Hours = (Time/60/60);
	                        new Minutes = (Time/60)%(60);
	                        new Seconds = (Time%60);

        
	
                                Format(texto,128, "n%i %s - %d h. %d m. %d s.", orden,name,Hours, Minutes, Seconds);   
    
                                AddMenuItem(menu2, numero, texto);


			}
		} 

		if(orden < 1) 
		{
			AddMenuItem(menu2, "empty", "TOP is empty!");
		}
		
                SetMenuExitButton(menu2, true);
                DisplayMenu(menu2, client, MENU_TIME_FOREVER);

}

public showTOP3(client){

	if (db != INVALID_HANDLE)
	{
		decl String:buffer[200];
		Format(buffer, sizeof(buffer), "SELECT playername, timeTT FROM mostactive ORDER BY timeTT DESC LIMIT 999");
		SQL_TQuery(db, SQLTopShow3, buffer, client);
	} else {
		PrintToChat(client, "Rank System is now not avilable");
	}
}

public SQLTopShow3(Handle:owner, Handle:hndl, const String:error[], any:client){

		if(hndl == INVALID_HANDLE)
		{
			LogError(error);
			PrintToServer("Last Connect SQL Error: %s", error);
			return;
		}

		new Handle:menu2 = CreateMenu(DIDMenuHandler2);
		SetMenuTitle(menu2, "Top T");


                new orden = 0;
                decl String:numero[64];
		decl String:name[64];
		decl String:texto[128];

		if (SQL_HasResultSet(hndl))
		{
			while (SQL_FetchRow(hndl))
			{
                                orden++;
                                Format(numero,64, "option%i", orden);
				SQL_FetchString(hndl, 0, name, sizeof(name));
	                        new Time = SQL_FetchInt(hndl,1);
	                        new Hours = (Time/60/60);
	                        new Minutes = (Time/60)%(60);
	                        new Seconds = (Time%60);
        
	
                                Format(texto,128, "n%i %s - %d h. %d m. %d s.", orden,name,Hours, Minutes, Seconds);    
    
                                AddMenuItem(menu2, numero, texto);


			}
		} 

		if(orden < 1) 
		{
			AddMenuItem(menu2, "empty", "TOP is empty!");
		}
		
                SetMenuExitButton(menu2, true);
                DisplayMenu(menu2, client, MENU_TIME_FOREVER);

}

public showTOP4(client){

	if (db != INVALID_HANDLE)
	{
		decl String:buffer[200];
		Format(buffer, sizeof(buffer), "SELECT playername, timeCT FROM mostactive ORDER BY timeCT DESC LIMIT 999");
		SQL_TQuery(db, SQLTopShow4, buffer, client);
	} else {
		PrintToChat(client, "Rank System is now not avilable");
	}
}

public SQLTopShow4(Handle:owner, Handle:hndl, const String:error[], any:client){

		if(hndl == INVALID_HANDLE)
		{
			LogError(error);
			PrintToServer("Last Connect SQL Error: %s", error);
			return;
		}

		new Handle:menu2 = CreateMenu(DIDMenuHandler2);
		SetMenuTitle(menu2, "Top CT");


                new orden = 0;
                decl String:numero[64];
		decl String:name[64];
		decl String:texto[128];

		if (SQL_HasResultSet(hndl))
		{
			while (SQL_FetchRow(hndl))
			{
                                orden++;
                                Format(numero,64, "option%i", orden);
				SQL_FetchString(hndl, 0, name, sizeof(name));
	                        new Time = SQL_FetchInt(hndl,1);
	                        new Hours = (Time/60/60);
	                        new Minutes = (Time/60)%(60);
	                        new Seconds = (Time%60);

        
	
                                Format(texto,128, "n%i %s - %d h. %d m. %d s.", orden,name,Hours, Minutes, Seconds); 
    
                                AddMenuItem(menu2, numero, texto);


			}
		} 

		if(orden < 1) 
		{
			AddMenuItem(menu2, "empty", "TOP is empty!");
		}
		
                SetMenuExitButton(menu2, true);
                DisplayMenu(menu2, client, MENU_TIME_FOREVER);

}

stock mostrartiempo(Time, any:client)
{

	new Hours = (Time/60/60);
	new Minutes = (Time/60)%(60);
	new Seconds = (Time%60);

        
	if(Hours >= 1)
        {
           PrintToChat(client, " \x04%d hours %d minutes %d seconds", Hours, Minutes, Seconds );
        }
        else if(Minutes >= 1)
        {
           PrintToChat(client, " \x04%d minutes %d seconds", Minutes, Seconds );
        }
        else PrintToChat(client, " \x04%d seconds", Seconds );
}

public DIDMenuHandler2(Handle:menu, MenuAction:action, client, itemNum) 
{
    /*if ( action == MenuAction_Select ) 
    {

    }*/
    if (action == MenuAction_Cancel) 
    { 
        PrintToServer("Client %d's menu was cancelled.  Reason: %d", client, itemNum); 
    } 

    else if (action == MenuAction_End)
    {
	CloseHandle(menu);
    }
} 

public Action:DOMenu(clientId,args)
{
    new Handle:menu = CreateMenu(DIDMenuHandler);
    SetMenuTitle(menu, "Most Active");
    AddMenuItem(menu, "option1", "View your time");
    AddMenuItem(menu, "option2", "View Top total played");
    AddMenuItem(menu, "option3", "View Top Spectator");
    AddMenuItem(menu, "option4", "View Top T");
    AddMenuItem(menu, "option5", "View Top CT");
    SetMenuExitButton(menu, true);
    DisplayMenu(menu, clientId, MENU_TIME_FOREVER);
    
    
    return Plugin_Handled;
}


public DIDMenuHandler(Handle:menu, MenuAction:action, client, itemNum) 
{
    if ( action == MenuAction_Select ) 
    {
        new String:info[32];
        
        GetMenuItem(menu, itemNum, info, sizeof(info));

        if ( strcmp(info,"option1") == 0 ) 
        {
              PrintToChat(client, " \x03Spectator:");	
              mostrartiempo(JugadoEspectador[client], client);
              PrintToChat(client, " \x03Terrorist:");
              mostrartiempo(JugadoT[client], client);
              PrintToChat(client, " \x03Counter-terrorist:");
              mostrartiempo(JugadoCT[client], client);
              PrintToChat(client, " \x03Total played:");
              new totalt = (JugadoT[client] + JugadoCT[client] + JugadoEspectador[client]);
              mostrartiempo(totalt, client);
              //DID(client);
        }

        else if ( strcmp(info,"option2") == 0 ) 
        {
              showTOP(client);
              //DID(client);
            
        }
        else if ( strcmp(info,"option3") == 0 ) 
        {
              showTOP2(client);
              //DID(client);
            
        }
        else if ( strcmp(info,"option4") == 0 ) 
        {
              showTOP3(client);
              //DID(client);
            
        }
        else if ( strcmp(info,"option5") == 0 ) 
        {
              showTOP4(client);
              //DID(client);
            
        }
    }
    else if (action == MenuAction_Cancel) 
    { 
        PrintToServer("Client %d's menu was cancelled.  Reason: %d", client, itemNum); 
    } 

    else if (action == MenuAction_End)
    {
	CloseHandle(menu);
    }
}
