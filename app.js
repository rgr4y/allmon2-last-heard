Vue.component('lt-startstop', {
  template: '<button class="pure-button pure-button-primary" @click="toggleTail" :class="{ \'button-error\': buttonState }">' +
    '{{ buttonState ? "Stop" : "Start" }} Tail</button>',
  data() {
    return {
      buttonState: false
    }
  },
  mounted() {
    this.toggleTail();
  },
  methods: {
    toggleTail() {
      this.buttonState = !this.buttonState;
      this.$emit('click', this.buttonState);
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
      uri: 'testdata.txt',
      lastData: null,
      logs: [],
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
  methods: {
    toggleTail(state) {
      this.enabled = state;

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
      axios.get(this.uri).then(({data}) => {
        this.lastData = data;
        this.parseLogData();
      });
    },
    
    parseLogData() {
      this.logs = [];
      let rows = this.lastData.split("\n").reverse();

      rows.forEach((v) => {
        let match = v.match(/([A-Za-z]+ [0-9]+ [0-9]+\:[0-9]+\:[0-9]+) (rpt|stn)([A-Za-z0-9]+) .*? (?:\[(?:via) ([0-9]+))?/);
        if (!match) return;

        let type = this.getNodeType(match[2]);
        this.addEntry(
          {
            node: match[3],
            via: match[4],
            type: type,
            typeLabel: this.getNodeTypeLabel(type),
            desc: this.fetchNodeInfo(match[3], type),
            dateTime: match[1],
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
      return type === 1 ? this.fetchNodeInfoAllstar(node)
        : (type === 2 ? this.fetchNodeInfoIRLP(node) : null);
    },

    fetchNodeInfoAllstar(node) {
      let info;
      return info;
    },

    fetchNodeInfoIRLP(node) {
      let info;
      return info;
    },

    addEntry(log) {
      this.logs.push(log);
    },
  }
});