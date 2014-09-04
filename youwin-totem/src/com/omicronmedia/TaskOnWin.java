/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package com.omicronmedia;

import java.util.HashMap;

/**
 *
 * @author Leonardo
 */
public interface TaskOnWin {

  public void setParentObject(YouWinTotem totem);
 public void exec(HashMap info, HashMap winInfo);

}
