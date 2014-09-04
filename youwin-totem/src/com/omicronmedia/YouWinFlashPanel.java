package com.omicronmedia;


import com.jpackages.jflashplayer.FlashPanel;
import com.jpackages.jflashplayer.JFlashInvalidFlashException;
import com.jpackages.jflashplayer.JFlashLibraryLoadFailedException;
import java.awt.Color;
import java.io.File;
import java.io.FileNotFoundException;
import java.net.URL;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 *
 * @author utente
 */
public class YouWinFlashPanel extends FlashPanel {

  public YouWinFlashPanel(File file) throws JFlashLibraryLoadFailedException, JFlashInvalidFlashException, FileNotFoundException  {
    super(file);
  }

  public YouWinFlashPanel(URL url) throws JFlashLibraryLoadFailedException, JFlashInvalidFlashException {
    super(url);
  }

  @Override
  public void paint(java.awt.Graphics g) {
    g.setColor(Color.GREEN);
    g.fillRect(0, 0, getSize().width, getSize().height);
  }
}
