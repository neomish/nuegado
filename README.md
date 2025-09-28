# Nuegado

Es una librería para apoyar desarrollos en php utilizando plantillas simples y funciones comunes al inicio de un proyecto, generalmente confines didácticos, permitiendo separar las capas de programas y las vistas.

Requiriendo un archivo de configuración para las variables de conexión en proyectos de aplicaciones locales.

## Uso

Con una inclusion simple de la librería y uso de las clases, ya sea directamente o por medio de las funciones de apoyo:

**index.php**
<pre>
    include "nuegado.php" ;
    echo cargarTpl( "indice.tpl" )          ;
</pre>

El archivo tpl es la plantilla con código html y etiquetas de llaves para contenido y bucles de listas

**indice.tpl**
<pre>
    &lt;html&gt;
        &lt;head&gt;
            &lt;title&gt;
                {TITULO}
            &lt;/title&gt;
        &lt;/head&gt;
        &lt;body&gt;
            ¡Hola {TEXTO}!
            &lt;ul&gt;
                {BUCLE:LISTA}
                    &lt;li&gt;
                        {e}
                    &lt;/li&gt;
                {BUCLEFIN:LISTA}
            &lt;/ul&gt;
            {PLANTILLA:extra.tpl}
        &lt;/body&gt;
    &lt;/html&gt;
</pre>

El archivo tpl puede tener o no un archivo con el mismo nombre pero con extensión php, de tal manera que si existe sera procesado previamente a la plantilla y podra enviar contenido o resultados a las variables encerradas en llaves, a travéz del arreglo **$_D**, en el que los índices son los nombres de las variables de la plantilla tpl

**indice.php**
<pre>
   &lt;?php
        $_D[ 'TITULO' ] = "Mi página" ;
        $_D[ 'TEXTO'  ] = "mundo"     ;
        $_D[ 'LISTA'  ] = [
            [ 'e' => "Elemento uno"  ] ,
            [ 'e' => "Elemento dos"  ] ,
            [ 'e' => "Elemento tres" ] ,
        ] ;
    ?&gt;
</pre>

Archivos tpl pueden ser invocados desde las plantillas utilizando {PLANTILLA:archivo.tpl}, los cuales siguen la misma función de preprocesar un archivo php si llegase a existir

**extra.tpl**
<pre>
    &lt;h2&gt;
        Cowabunga
    &lt;/h2&gt;
</pre>

## Licencia

Librería para gestión de plantillas con PHP
Autor: José David Calderón Serrano
jose.david en calderonbonilla.org, neomish en gmail.com, gato en kalmish.com

Este programa/libreria es software libre: usted puede redistribuirlo y/o modificarlo bajo los términos de la GNU General Public License publicada por la Free Software Foundation, bien de la versión 3 de la Licencia, o (a su elección) cualquier versión posterior.

Este programa se distribuye con la esperanza de que sea útil, pero SIN NINGUNA GARANTÍA, incluso sin la garantía implícita de COMERCIALIZACIÓN o IDONEIDAD PARA UN PROPÓSITO PARTICULAR. Consulte la GNU General Public License para más detalles.

Debería haber recibido una copia de la Licencia Pública General GNU junto con este programa, vease el archivo licencia-gpl.v3.es.txt, Si no, vea https://www.gnu.org/licenses/gpl.html
