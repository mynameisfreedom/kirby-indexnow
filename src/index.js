window.panel.plugin("mynameisfreedom/kirby-indexnow", {
  components: {
    "k-indexnow-view": {
      props: {
        enabled: {
          type: Boolean,
          default: false
        },
        endpoint: {
          type: String,
          default: "https://api.indexnow.org/indexnow"
        },
        lines: {
          type: Array,
          default: () => []
        },
        logRoute: {
          type: String,
          default: ""
        },
        csrf: {
          type: String,
          default: ""
        }
      },
      computed: {
        logText() {
          return this.lines.length > 0
            ? this.lines.join("\n")
            : "No log entries yet.";
        }
      },
      methods: {
        async submitAction(action) {
          if (!this.logRoute) {
            return;
          }

          if (action === "clear") {
            const ok = window.confirm("Clear the log file?");
            if (!ok) {
              return;
            }
          }

          const formData = new FormData();
          formData.append("indexnow_action", action);
          formData.append("csrf", this.csrf);

          await fetch(this.logRoute, {
            method: "POST",
            body: formData,
            credentials: "same-origin"
          });

          this.$reload();
        }
      },
      render(h) {
        const self = this;

        const actionButtons = h("div", { class: "k-indexnow-actions" }, [
          h("k-button", {
            props: {
              icon: "refresh",
              text: "Reload",
              variant: "filled"
            },
            on: {
              click() {
                self.$reload();
              }
            }
          }),
          h("k-button", {
            props: {
              icon: "wand",
              text: "Write Test Line",
              variant: "filled"
            },
            on: {
              click() {
                self.submitAction("test");
              }
            }
          }),
          h("k-button", {
            props: {
              icon: "trash",
              text: "Clear Log",
              theme: "negative",
              variant: "filled"
            },
            on: {
              click() {
                self.submitAction("clear");
              }
            }
          })
        ]);

        const statusBox = h("k-box", {
          class: "k-indexnow-status-box",
          props: { theme: self.enabled ? "positive" : "warning" }
        }, [
          h("p", [h("strong", "Status: "), self.enabled ? "Enabled" : "Disabled"])
        ]);

        const endpointBox = h("k-box", {
          class: "k-indexnow-status-box",
          props: { theme: "info" }
        }, [
          h("p", [h("strong", "Endpoint: "), self.endpoint])
        ]);

        const logHeading = h("p", {
          class: "k-indexnow-log-heading"
        }, ["Last 500 lines"]);

        const logBox = h("k-box", {
          class: "k-indexnow-log-box",
          props: { theme: "passive" }
        }, [
          h("pre", self.logText)
        ]);

        return h("k-panel-inside", [
          h("div", { class: "k-indexnow-view" }, [
            h("k-header", ["IndexNow"]),
            actionButtons,
            statusBox,
            endpointBox,
            logHeading,
            logBox
          ])
        ]);
      }
    }
  }
});
