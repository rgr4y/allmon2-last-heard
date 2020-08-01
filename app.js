/**
 * WIN System Log Tail / Who's Talking?
 * 
 * @author Rob Vella KK9ROB <me@robvella.com>
 * @type {Vue}
 */

Vue.component('lt-node-link', {
  template: `<a :href='getUrl()' target="_blank">{{ node }}</a>`,
  props: ['node', 'type'],
  data() {
    return {
      urlMap: {
        0: '#',
        1: 'http://stats.allstarlink.org/nodeinfo.cgi?node=',
        2: 'http://www.irlp.net/status/index.php?nodeid='
      }
    }
  },
  methods: {
    getUrl() {
      return this.urlMap[this.type] + this.node;
    }
  }
});

let App = new Vue({
  el: '#app',
  data() {
    return {
      enabled: false,
      intervalTimer: null,
      fetchEvery: 2000,
      // uri = 'http://www3.winsystem.org/monitor/ajax-logtail.php'
      uri: 'fetchData.php',
      lastData: null,
      logs: [],
      nodes: {},
      nodeTypeLabels: {
        0: 'Unknown',
        1: 'Allstar',
        2: 'IRLP'
      },
      nodeTypePrefixes: {
        'rpt': 1,
        'stn': 2
      }
    }
  },
  mounted() {
    this.toggleTail();
  },
  methods: {
    toggleTail() {
      this.enabled = !this.enabled;

      if (this.enabled) {
        this.start();
      } else {
        this.stop();
      }
    },
    
    start() {
      this.intervalTimer = setInterval(() => {
        this.fetchLog();
      }, this.fetchEvery);

      this.fetchLog();
    },
    
    stop() {
      clearInterval(this.intervalTimer);
    },
    
    fetchLog() {
      // Force re-render component so it updates callsigns & node info
      this.$forceUpdate();
      
      axios.get(this.uri + '?cmd=log').then(({data}) => {
        this.lastData = data;
        this.parseLogData();
        this.logs = this.logs.slice(0,500); 
      });
    },
    
    getNodeInfo(node) {
      if (typeof this.nodes[node.type+node.node] !== "undefined") {
        let info = this.nodes[node.type+node.node];
        if (typeof info.callsign === "undefined") return;
        return `${info.callsign} ${info.desc || ''} ${info.location || ''}`;
      } else {
        return '';
      }
    },
    
    parseLogData() {
      let rows = this.lastData.split("\n");

      rows.forEach((v) => {
        // let match = v.match(/([A-Za-z]+ [0-9]+ [0-9]+\:[0-9]+\:[0-9]+) (rpt|stn)([A-Za-z0-9]+) ?.*? (?:\[(?:via) ([0-9]+))?/);
        let match = v.match(/([A-Za-z]+ [0-9]+ [0-9]+\:[0-9]+\:[0-9]+) (rpt|stn)([A-Za-z0-9]+) (KEY|UNKEY) (?:\[(?:via) ([0-9]+)\])?(?: \[(.*)\])?/);
        if (!match) return;
        console.log(match);
        let type = this.getNodeType(match[2]);
        
        this.addEntry(
          {
            node: match[3],
            key: match[4] || "KEY",
            via: match[5],
            type: type,
            typeLabel: this.getNodeTypeLabel(type),
            // info: this.fetchNodeInfo(match[3], type),
            info: typeof match[6] !== "undefined" ? match[6].trim() : undefined,
            dateTime: moment(match[1], "MMM DD hh:mm:ss"),
          }
        );
      });
    },
    
    getNodeType(type) {
      return typeof this.nodeTypePrefixes[type] !== 'undefined' ? this.nodeTypePrefixes[type] : 0;
    },

    getNodeTypeLabel(type) {
      return this.nodeTypeLabels[type];
    },

    fetchNodeInfo(node, type) {
      if (type === 0) return;
      
      if (this.nodes[type+node]) {
        // Don't even call fetchNode**
        return this.nodes[type+node];
      }
      
      axios.get(this.uri + '?cmd=node&type='+type+'&node='+node).then(({data}) => {
        this.nodes[type+node] = data;
      });
    },

    addEntry(log) {
      // Generate unique ID for this entry
      log.uniqId = (log.node + log.dateTime.unix()).hashCode();
      if (!_.findWhere(this.logs, { uniqId: log.uniqId })) {
        this.logs.unshift(log);
      }
    },
  }
});

String.prototype.hashCode = function() {
  let hash = 0;
  if (this.length === 0) {
    return hash;
  }
  for (var i = 0; i < this.length; i++) {
    let char = this.charCodeAt(i);
    hash = ((hash<<5)-hash)+char;
    hash = hash & hash; // Convert to 32bit integer
  }
  return Math.abs(hash);
};