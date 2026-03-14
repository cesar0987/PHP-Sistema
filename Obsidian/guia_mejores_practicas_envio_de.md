RecomendacionesymejoresprácticasparaSIFEN Guíaparaeldesarrollador
Octubre2024 1

Índice Introducción 3GeneracióndelosDocumentosElectrónicos
4GeneracióndeLotesdeDocumentosElectrónicos 5ServiciosWebAsíncronos:
5Recomendaciones 5Lotenoencoladoparaprocesamiento 6Motivosderechazo
6Motivosdebloqueo 6InvocacióndeWebServiceAsíncronos 7recibe-lote
8consulta-lote 10consulta 12 2

Introducción Estedocumentoestáorientadoal
usuariodesarrolladordeservicioswebdeintegraciónconSIFEN, es una
aplicación práctica deloespecificadoenel Manual
TécnicodeSistemadeFacturaciónElectrónicaNacional
referentealarecepcióndedocumentoselectrónicos(DE)por lotes. Se da por
entendido que el usuario tiene los conocimientos necesarios
ysuficientesdelassiguientesnormasyestándares: ● XML● SOAP, versión1.2●
HTTP● ProtocolodeseguridadTLSversión1.2, conautenticaciónmutua●
Estándardecertificadoyfirmadigital○ EstándardeFirma: XMLDigital
Signature, formatoEnvelopedW3C○ CertificadoDigital: Expedidopor unadelas
PSChabilitados enlaRepúblicadel Paraguay,
estándarhttp://www.w3.org/2000/09/xmldsig#X509Data○
TamañodelaClaveCriptográfica: RSA2048, paracifradoporsoftware.○
FunciónCriptográficaAsimétrica:
RSAconformeahttps://www.w3.org/TR/2002/REC-xmlenc-core20021210/Overview.html#rsa-1_5
.○ Funciónde"messagedigest":
SHA-2https://www.w3.org/TR/2002/REC-xmlenc-core-20021210/Overview.html#sha256○
Codificación: Base64https://www.w3.org/TR/xmldsig-core1/#sec-Base-64○
Transformaciones exigidas: Útil para canonizar el XML enviado, con
elpropósito de realizar la validación correcta de la firma digital:
Enveloped,https://www.w3.org/TR/xmldsig-core1/#sec-EnvelopedSignature
C14N,http://www.w3.org/2001/10/xml-exc-c14n# 3

GeneracióndelosDocumentosElectrónicos Para mayor información se debe
consultar el Manual Técnico en la versión que se estáutilizando,
porejemploparael MT150
\<rDExmlns="http://ekuatia.set.gov.py/sifen/xsd"xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"xsi:schemaLocation="http://ekuatia.set.gov.py/sifen/xsdsiRecepDE_v150.xsd"\>
TenerprecaucióndeNOincorporar: 1. Espaciosenblancoenel iniciooenel final
decamposnuméricosyalfanuméricos.2. Comentarios, anotaciones y
documentaciones, léase las etiquetas annotation ydocumentation.3.
Caracteres de formato de archivo, como line-feed, carriage return, tab,
espaciosentreetiquetas.4. Prefijosenel namespacedelasetiquetas.5.
Etiquetas decampos quenocontenganvalor, seanestas numéricas,
quecontienenceros, vacíos oblancos paracampos del tipoalfanumérico.
EstánexcluidosdeestareglatodosaquelloscamposidentificadoscomoobligatoriosenlosdistintosformatosdearchivoXML,
laobligatoriedaddelosmismosseencuentraplenamentedetalladaenel manual
técnico.6. Valoresnegativosocaracteresnonuméricosencamposnuméricos.7. El
nombredelos campos es sensibleaminúsculas y mayúsculas,
porloquedebensercomunicadosdelamismaformaenlaquesevisualizaenel manual
técnico.Ejemplo: el grupo gOpeDE, es diferente a GopeDE, a gopede y a
cualquier otracombinacióndistintaalainicial. La DNIT disponibiliza una
herramienta para pre validación del DE en tiempo dedesarrollo,
utilidadparadetectarcamposincorrectosenel XML. PrevalidadorSIFEN:
https://ekuatia.set.gov.py/prevalidador/ 4

GeneracióndeLotesdeDocumentosElectrónicos Los documentos electrónicos
seenvíanaSIFENenlotes, el procesamientodeloslotesserealizaatravés
delarecepcióndevariosDEenunarchivocomprimidoparaprocesarlosdeforma
asíncrona. El resultado del procesamiento de un lote se debe consultar
en unsegundomomento, separadodel envío. ServiciosWebAsíncronos:
Tenerencuentalosdominiosparacadaambiente: 1. AmbientedeProducción:
sifen.set.gov.py2. AmbientedeTest (pruebas): sifen-test.set.gov.py ●
RecepciónloteDERecibeel
lotedeDE(hasta50DE)paraprocesarlosenunacoladeespera.https://{ambiente}/de/ws/async/recibe-lote.wsdl
● ConsultaresultadoloteConsultael
estadodeunloterecibidopreviamente.https://{ambiente}/de/ws/consultas/consulta-lote.wsdl
● ConsultaporCDCConsultaunDE, si estáaprobadoretornael XMLdel
DE.https://{ambiente}/de/ws/consultas/consulta.wsdl Paraobtenerel
WSDLdecadaservicio, agregaral final decadaurl ?wsdl,
porejemploparalaconsultaderesultadoloteel wsdl
seobtieneconlasiguienteurl:https://{ambiente}/de/ws/consultas/consulta-lote.wsdl?wsdl
Ladescripcióndeestructuras y las restricciones delos contenidos
delosdocumentosXMLse encuentran especificados en el Manual Técnico,
correspondiente alaversión, y
enlosschemasXSDdeSIFENqueestánpublicadosenhttp://ekuatia.set.gov.py/sifen/xsd
Recomendaciones 1.
Enviarlamáximacantidadposiblededocumentosenunlote(hasta50documentos).2.
Verificar la respuesta de la recepción del lote, considerandolos
siguientes códigosderespuesta: 5

a.  Lote recibido con éxito (0300), el lote será procesado,
    sedebeconsultar elestado, através del númerodeloteretornado,
    paraobtener el detalledelosdocumentosenviados.b. Lote no encolado
    para procesamiento (0301), el lote NO será
    procesado,verificarlasección"Lotenoencoladoparaprocesamiento".3.
    Cuando se envía un lote y nosereciberespuestadel SIFENpor
    algúncorteenlacomunicación, se puede consultar el lote con un CDC
    que fue enviado en el loterespectivo. Deestamaneraobtendráel
    resultadodel estadodel loteyel númerodelote correspondiente.
    Utilizar esta opción solo en caso de no recibir el
    NúmerodeLotecomorespuestaal envío.4. La consulta de un lote recibido
    se debe realizar luego de pasado un periodo detiemponomuy
    cortodelarecepción, teniendoencuentaqueSIFENtieneunacolade
    procesamiento que puede variar de acuerdo a la fecha y horas pico de
    lasactividadescomerciales. Si bienel
    procesamientoporcadaDEestádefinidocercanoa los 1 segundo, se
    recomienda comenzar a realizar la consulta pasados los
    10minutosdelarecepciónyluegoaintervalosregularesnomenoresa10minutos.5.
    Nunca se debe enviar un mismo CDC sin haber tenido la respuesta
    definitiva deSIFEN (Aprobado, Aprobado con Observación oRechazado),
    es decir, consultar elresultado del procesamiento del lote con el WS
    de consulta de lote. Así
    tambiéntenerencuentalasreglasdebloqueodeRUCporenvíosduplicadosdeDE.
    Lotenoencoladoparaprocesamiento
    MotivosderechazoLarecepcióndeunlotesepuederechazarporlossiguientesmotivos:
b.  HaberenvíadoDEcondistintosRUCemisores.SedebeenviardocumentosdeunsoloRUCemisorporlote.b.
    HaberenvíadoDEdedistintostipos.Se debe enviar documentos de un solo
    tipo de documento por lote (soloFacturaElectrónica,
    soloNotadeCrédito, etc).c. Haberenviadomásde50DEenunmismoloted.
    Estarbloqueadoporenvíoduplicado, verMotivosdebloqueoe. El tamañodel
    archivocomprimidoenviadosuperael tamañopermitido.El
    mensajededatosdeentradadel WSnodebesuperar1000KB.
    MotivosdebloqueoLas siguientes operaciones
    generanbloqueoderecepcióndedocumentosporRUCEmisor de 10 a60minutos,
    segúnlacantidaddereincidencia. Estopuedegenerar 6

algún esquema de penalización en el futuro. Los motivos del
bloqueotemporal derecepciónporRUCson:
f. Enviarlotesvacíosoconcontenidonoválido.g. Enviarel
mismoCDCvariasvecesenunmismolote.h. Enviar el
mismoCDCvariasvecesenlotesdistintosyqueaúnseencuentrenenprocesamiento.Antes
de volver aenviar unDE(mismoCDCenviado) sedebeverificar
quenoestéaúnenprocesamiento, conlaconsultadelote.i.
Enviarvariasvecesunmismolote. InvocacióndeWebServiceAsíncronos 1.
Sedebeprestar muchaatenciónalos namespaceespecificados
paracadaservicioweb.2. Se realiza una autenticación mutua con SIFEN a
través de un certificado digitalemitido por una PSC habilitada. El medio
para establecer estacomunicaciónes laInternet, apoyado en la utilización
del protocolo de seguridadTLSversión1.2, conautenticaciónmutua.
Configuracióndeconexiónparaautenticaciónmutua(enPostman) 7

recibe-loteRecepción de DE por lotes, para consumir este servicio, el
cliente deberá construir laestructura en XML, según el schema
WS_SiRecepLoteDE.xsd y comprimir dicho archivo.Cabe aclarar que el lote
podrá contener hasta 50 DE del mismo tipo (ejemplo:
FacturasElectrónicas), cadaunodeellosdebeestarfirmado.
InvocaciónPOST(enPostman) RequestBodyPasosparacrearel
Bodyparalainvocacióndel servicio: 1. Crearlaestructuradel
lote`<rLoteDE>`{=html}...`</rLoteDE>`{=html} 2.
InsertarlosDEfirmadosenlaestructuradel
lote`<rLoteDE>`{=html}`<rDE>`{=html}...`</rDE>`{=html}`<rDE>`{=html}...`</rDE>`{=html}...`</rLoteDE>`{=html}
3. Comprimirel contenidodelaestructuradel lote"rLoteDE"4. Convertirel
contenidocomprimidoaBase645. Crearel envelopesoap,
teniendoencuentalosnamespaceespecificados 8

\<soap:Envelopexmlns:soap="http://www.w3.org/2003/05/soap-envelope"xmlns:xsd="http://ekuatia.set.gov.py/sifen/xsd"\>`<soap:Header/>`{=html}\<soap:Body\<`<xsd:rEnvioLote>`{=html}`<xsd:dId>`{=html}20240926`</xsd:dId>`{=html}`<xsd:xDE>`{=html}{AquívaelBase64delpunto4}`</xsd:xDE>`{=html}`</xsd:rEnvioLote>`{=html}`</soap:Body>`{=html}`</soap:Envelope>`{=html}
6. Verificarlarespuestadelainvocación"rResEnviLoteDe "
`<?xmlversion="1.0"encoding="UTF-8"?>`{=html}\<env:Envelopexmlns:env="http://www.w3.org/2003/05/soap-envelope"\>`<env:Header/>`{=html}`<env:Body>`{=html}\<ns2:rResEnviLoteDexmlns:ns2="http://ekuatia.set.gov.py/sifen/xsd"\>`<ns2:dFecProc>`{=html}2024-10-08T14:51:21-03:00`</ns2:dFecProc>`{=html}`<ns2:dCodRes>`{=html}0300`</ns2:dCodRes>`{=html}`<ns2:dMsgRes>`{=html}Loterecibidoconéxito`</ns2:dMsgRes>`{=html}`<ns2:dProtConsLote>`{=html}11158097383597290`</ns2:dProtConsLote>`{=html}`<ns2:dTpoProces>`{=html}0`</ns2:dTpoProces>`{=html}`</ns2:rResEnviLoteDe>`{=html}`</env:Body>`{=html}`</env:Envelope>`{=html}
ResponseLa respuesta, se debe analizar el campo "dCodRes", puede indicar
una de las situacionessiguientes 1. Lote recibido con éxito (0300), el
lote seráprocesado, sedebeconsultar el estadopara obtener el detalle de
los documentos enviados. Se sugiere comenzar
aconsultarunloteenviadopasado10minutosdesdeel envío.2.
Lotenoencoladoparaprocesamiento(0301), el loteNOseráprocesado,
verificarlasección"Lotenoencoladoparaprocesamiento". 9

consulta-loteDevuelveel resultadodel
procesamientodecadaunodelosDEcontenidosenunlote.Segúnel
schemaWS_SiConsLote.xsd. RequestBodyLaconsultaserealizaporel valordel
campo"dProtConsLote"queformapartedelresponsedelarecepcióndel lote.
\<soap:Envelopexmlns:soap="http://www.w3.org/2003/05/soap-envelope"xmlns:xsd="http://ekuatia.set.gov.py/sifen/xsd"\>`<soap:Header/>`{=html}`<soap:Body>`{=html}`<xsd:rEnviConsLoteDe>`{=html}`<xsd:dId>`{=html}1`</xsd:dId>`{=html}`<xsd:dProtConsLote>`{=html}11158097383597290`</xsd:dProtConsLote>`{=html}`</xsd:rEnviConsLoteDe>`{=html}`</soap:Body>`{=html}`</soap:Envelope>`{=html}
ResponseLa respuesta, se debe analizar el campo "dCodResLot", puede
indicar una de las cuatrosituacionessiguientes: 1.
Noexistenúmerodeloteconsultado. 0360Númerodel Loteinexistente2.
Nosehaculminadoel procesamientodelosDE. 0361Loteenprocesamiento.
Debeconsultar nuevamente el lote, se sugiere consultar a intervalos
mínimos de 10minutos. En momentos de alta carga el procesamiento puede
ocurrir entre 1 a 24horasposterioresalarecepción.3. Consulta
extemporánea de Lote. 0364La consulta del lote contempla
unplazodehasta48horas posteriores al envíodel mismo. Unavezsuperadoel
tiempo, deberáconsultarcadaCDCdel lotemediantelaWSConsultaDE4.
Éxitoenlaconsulta.
0362Procesamientodeloteconcluido.Larespuestatambiéncontieneel
contenedordel DE, definidoenel Schema. A. Enprocesamiento
\<env:Envelopexmlns:env="http://www.w3.org/2003/05/soap-envelope"\>`<env:Header/>`{=html}
10

`<env:Body>`{=html}\<ns2:rResEnviConsLoteDexmlns:ns2="http://ekuatia.set.gov.py/sifen/xsd"\>`<ns2:dFecProc>`{=html}2024-10-08T14:53:53-03:00`</ns2:dFecProc>`{=html}`<ns2:dCodResLot>`{=html}0361`</ns2:dCodResLot>`{=html}`<ns2:dMsgResLot>`{=html}Lote{11158097383597290}enprocesamiento`</ns2:dMsgResLot>`{=html}`</ns2:rResEnviConsLoteDe>`{=html}`</env:Body>`{=html}`</env:Envelope>`{=html}
B. ProcesamientoconcluidoSeindicael detalledel procesamientoCDCenel
elemento"gResProcLote "
\<env:Envelopexmlns:env="http://www.w3.org/2003/05/soap-envelope"\>`<env:Header/>`{=html}`<env:Body>`{=html}\<ns2:rResEnviConsLoteDexmlns:ns2="http://ekuatia.set.gov.py/sifen/xsd"\>`<ns2:dFecProc>`{=html}2024-10-08T03:58:16-03:00`</ns2:dFecProc>`{=html}`<ns2:dCodResLot>`{=html}0362`</ns2:dCodResLot>`{=html}`<ns2:dMsgResLot>`{=html}Procesamientodelote{11444651783497640}concluido`</ns2:dMsgResLot>`{=html}`<ns2:gResProcLote>`{=html}`<ns2:id>`{=html}07800252985001001000311822024021016361562161`</ns2:id>`{=html}`<ns2:dEstRes>`{=html}Rechazado`</ns2:dEstRes>`{=html}`<ns2:gResProc>`{=html}`<ns2:dCodRes>`{=html}0160`</ns2:dCodRes>`{=html}`<ns2:dMsgRes>`{=html}XMLmalformado:\[Elvalordelelemento:dDirRecesinvalido,Elvalordelelemento:dDirLocEntesinvalido\]`</ns2:dMsgRes>`{=html}`</ns2:gResProc>`{=html}`</ns2:gResProcLote>`{=html}`</ns2:rResEnviConsLoteDe>`{=html}`</env:Body>`{=html}`</env:Envelope>`{=html}
11

consultaDevuelveel XMLdeunDEqueestáenestadoaprobado. Segúnel
schemaWS_SiConsDE.xsd RequestBodyLaconsultaserealizaporel valordel
campo"dCDC ".
\<soap:Envelopexmlns:soap="http://www.w3.org/2003/05/soap-envelope"xmlns:xsd="http://ekuatia.set.gov.py/sifen/xsd"xmlns:si="http://ekuatia.set.gov.py/sifen/xsd"\>`<soap:Header/>`{=html}`<soap:Body>`{=html}`<xsd:rEnviConsDeRequest>`{=html}`<xsd:dId>`{=html}12`</xsd:dId>`{=html}`<xsd:dCDC>`{=html}01028052080001001000013622023100111644108186`</xsd:dCDC>`{=html}`</xsd:rEnviConsDeRequest>`{=html}`</soap:Body>`{=html}`</soap:Envelope>`{=html}
ResponseLarespuesta, sedebeanalizarel campo"dCodRes ",
puedeindicarunadelasdossituacionessiguientes:1. 0420El DE no existe o no
está aprobado, se debe volver a enviar el DE parasuprocesamiento, tener
encuentael resultadodelaconsultapor loteantes deenviarnuevamente.2. 0422
Existe como DTE, está aprobado, se responde el contenido XML del DE
en"xContenDE " A. Noexiste
\<env:Envelopexmlns:env="http://www.w3.org/2003/05/soap-envelope"\>`<env:Header/>`{=html}`<env:Body>`{=html}\<ns2:rEnviConsDeResponsexmlns:ns2="http://ekuatia.set.gov.py/sifen/xsd"\>`<ns2:dFecProc>`{=html}2024-10-09T09:28:39-03:00`</ns2:dFecProc>`{=html}`<ns2:dCodRes>`{=html}0420`</ns2:dCodRes>`{=html}`<ns2:dMsgRes>`{=html}DocumentoNoExisteenSIFENo
hasidoRechazado`</ns2:dMsgRes>`{=html} 12

`</ns2:rEnviConsDeResponse>`{=html}`</env:Body>`{=html}`</env:Envelope>`{=html}
B. Existe
\<env:Envelopexmlns:env="http://www.w3.org/2003/05/soap-envelope"\>`<env:Header/>`{=html}`<env:Body>`{=html}\<ns2:rEnviConsDeResponsexmlns:ns2="http://ekuatia.set.gov.py/sifen/xsd"\>`<ns2:dFecProc>`{=html}2023-10-02T15:13:52-03:00`</ns2:dFecProc>`{=html}`<ns2:dCodRes>`{=html}0422`</ns2:dCodRes>`{=html}`<ns2:dMsgRes>`{=html}CDCencontrado`</ns2:dMsgRes>`{=html}`<ns2:xContenDE>`{=html}{contenidoXMLdelDE}`</ns2:xContenDE>`{=html}`</ns2:rEnviConsDeResponse>`{=html}`</env:Body>`{=html}`</env:Envelope>`{=html}
13
