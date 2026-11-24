L.Geoserver = L.TileLayer.WMS.extend({
  initialize: function (baseLayerUrl, options) {
    var defaultWMSParams = {
      service: "WMS",
      request: "GetMap",
      version: "1.1.1",
      layers: "",
      styles: "",
      format: "image/png",
      transparent: true,
    };

    var wmsParams = L.extend({}, defaultWMSParams);
    var tileOptions = {};

    for (var i in options) {
      if (i === "pane" || i === "opacity" || i === "zIndex" || i === "attribution") {
        tileOptions[i] = options[i];
      } else if (!(i in this.options)) {
        wmsParams[i] = options[i];
      }
    }

    L.Util.setOptions(this, tileOptions);
    L.TileLayer.WMS.prototype.initialize.call(this, baseLayerUrl, wmsParams);
  },
});

L.Geoserver.wms = function (baseLayerUrl, options) {
  return new L.Geoserver(baseLayerUrl, options);
};

L.Geoserver.Legend = L.Control.extend({
  options: {
    position: "bottomleft",
  },
  initialize: function (baseLayerUrl, options) {
    this.baseLayerUrl = baseLayerUrl;
    L.Util.setOptions(this, options);
  },
  onAdd: function (map) {
    var div = L.DomUtil.create("div", "info legend");
    div.style.backgroundColor = "white";
    div.style.padding = "8px";
    div.style.borderRadius = "4px";
    div.style.boxShadow = "0 0 15px rgba(0,0,0,0.2)";

    var url =
      this.baseLayerUrl +
      "?REQUEST=GetLegendGraphic&VERSION=1.0.0&FORMAT=image/png&WIDTH=20&HEIGHT=20&LAYER=" +
      this.options.layers;

    div.innerHTML = '<img src="' + url + '" alt="Legend">';
    return div;
  },
});

L.Geoserver.legend = function (baseLayerUrl, options) {
  return new L.Geoserver.Legend(baseLayerUrl, options);
};