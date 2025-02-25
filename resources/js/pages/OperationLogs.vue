<template>
  <div class="operation-logs">
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">操作日志</h3>
      </div>
      <div class="card-body">
        <!-- 搜索过滤区 -->
        <div class="filters mb-4">
          <el-form :inline="true" :model="filters">
            <el-form-item label="模块">
              <el-select v-model="filters.module" placeholder="选择模块" clearable>
                <el-option
                  v-for="item in modules"
                  :key="item"
                  :label="item"
                  :value="item"
                />
              </el-select>
            </el-form-item>
            <el-form-item label="操作类型">
              <el-select v-model="filters.action" placeholder="选择操作类型" clearable>
                <el-option
                  v-for="item in actions"
                  :key="item"
                  :label="item"
                  :value="item"
                />
              </el-select>
            </el-form-item>
            <el-form-item>
              <el-button type="primary" @click="fetchLogs">查询</el-button>
              <el-button @click="resetFilters">重置</el-button>
            </el-form-item>
          </el-form>
        </div>

        <!-- 日志表格 -->
        <el-table
          :data="logs"
          border
          style="width: 100%"
          v-loading="loading"
        >
          <el-table-column prop="user.name" label="操作人" width="120" />
          <el-table-column prop="module" label="模块" width="120" />
          <el-table-column prop="action" label="操作类型" width="120" />
          <el-table-column prop="description" label="描述" />
          <el-table-column prop="ip_address" label="IP地址" width="120" />
          <el-table-column label="操作时间" width="180">
            <template slot-scope="scope">
              {{ new Date(scope.row.created_at).toLocaleString() }}
            </template>
          </el-table-column>
          <el-table-column label="详情" width="100">
            <template slot-scope="scope">
              <el-button
                type="text"
                @click="showDetails(scope.row)"
              >
                查看
              </el-button>
            </template>
          </el-table-column>
        </el-table>

        <!-- 分页 -->
        <div class="pagination-container">
          <el-pagination
            background
            layout="total, prev, pager, next"
            :current-page="currentPage"
            :page-size="pageSize"
            :total="total"
            @current-change="handleCurrentChange"
          />
        </div>

        <!-- 详情弹窗 -->
        <el-dialog
          title="操作详情"
          :visible.sync="detailsVisible"
          width="50%"
        >
          <pre>{{ formatDetails(selectedLog) }}</pre>
        </el-dialog>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'OperationLogs',
  
  data() {
    return {
      logs: [],
      modules: [],
      actions: [],
      loading: false,
      currentPage: 1,
      pageSize: 15,
      total: 0,
      filters: {
        module: '',
        action: '',
        search: ''
      },
      detailsVisible: false,
      selectedLog: null
    }
  },

  created() {
    this.fetchLogs()
  },

  methods: {
    async fetchLogs() {
      this.loading = true
      try {
        const params = {
          page: this.currentPage,
          per_page: this.pageSize,
          module: this.filters.module,
          action: this.filters.action,
          search: this.filters.search
        }

        const response = await axios.get('/api/operation-logs', { params })
        this.logs = response.data.data.data
        this.total = response.data.data.total
        this.modules = response.data.modules
        this.actions = response.data.actions
      } catch (error) {
        this.$message.error('获取日志失败')
        console.error(error)
      } finally {
        this.loading = false
      }
    },

    handleCurrentChange(val) {
      this.currentPage = val
      this.fetchLogs()
    },

    resetFilters() {
      this.filters = {
        module: '',
        action: '',
        search: ''
      }
      this.currentPage = 1
      this.fetchLogs()
    },

    showDetails(log) {
      this.selectedLog = log
      this.detailsVisible = true
    },

    formatDetails(log) {
      if (!log || !log.details) return ''
      return JSON.stringify(log.details, null, 2)
    }
  }
}
</script>

<style scoped>
.operation-logs {
  padding: 20px;
}
.pagination-container {
  margin-top: 20px;
  text-align: right;
}
</style> 