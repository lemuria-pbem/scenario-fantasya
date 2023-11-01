# Skripte

Nichtspielercharaktere werden über Skripte gesteuert. Skripte können neue
Einheiten erzeugen, oder einfache Befehle und komplexe Handlungen für die
Einheiten setzen.

Skripte werden im INI-Format abgelegt; jede Sektion ist ein Skript.

## Allgemeine Parameter

### ID

Die Nummer einer Einheit wird einheitlich mit dem Schlüssel _ID_ oder hinter dem
Skriptnamen in der Sektion angegeben:

    ID = e23

    [Skript e23]

### Runde

Jedes Skript kann den Schlüssel _Runde_ enthalten, der angibt, in welcher Runde
das Skript ausgeführt wird.

    Runde = 123

## Einheit

Das Skript **[Einheit]** erzeugt eine neue Einheit. Die _ID_ ist optional und
wird nur benötigt, wenn andere Skripte sich auf die neue Einheit beziehen.

Notwendige Schlüssel:

    Name = Galbrak, der Fahrende
    Rasse = Zwerg
    Region = 1re

Optionale Schlüssel:

    Anzahl = 1
    Beschreibung = Ein fahrender Händler auf den Straßen von Lemuria.
    Besitz = 1 Wagen, 2 Pferde, 2000 Silber
    Besitz = 20 Juwelen, 40 Balsam, 40 Pelze
    Talent = Handeln 5, Ausdauer 1
    Talent = Reiten 2

## Skript

Eine Sektion **[Skript]** legt einfache Befehle oder komplexe Handlungen für
eine Einheit fest.

Befehle und Handlungen können gemischt werden.

### Befehle

    [Skript h]
    VORGABE Wiederholen
    ANGEBOT * Juwelen 1 Myrrhe

### Handlungen

Handlungen werden als _Makro_ mit Parametern in Klammern angegeben; dadurch
werden sie von Befehlen unterschieden.

    [Skript]
    ID = h
    Marktstand(3)
    Rundreise(r1, r2, r3)
