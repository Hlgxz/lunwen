<template>
  <div class="appeals-list">
    <el-card>
      <el-form :inline="true" :model="searchForm" class="search-form" @submit.native.prevent="handleSearch">
        <el-form-item label="论文标题">
          <el-input
            v-model="searchForm.title"
            placeholder="请输入论文标题"
            clearable
          ></el-input>
        </el-form-item>
        <el-form-item label="申诉状态">
          <el-select v-model="searchForm.status" placeholder="请选择状态" clearable>
            <el-option label="待处理" value="pending"></el-option>
            <el-option label="处理中" value="processing"></el-option>
            <el-option label="已完成" value="completed"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="申诉结果">
          <el-select v-model="searchForm.result" placeholder="请选择结果" clearable>
            <el-option label="待定" value="pending"></el-option>
            <el-option label="已通过" value="approved"></el-option>
            <el-option label="已驳回" value="rejected"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="resetSearch">重置</el-button>
        </el-form-item>
      </el-form>

      <el-table
        :data="appealsList"
        border
        style="width: 100%"
        v-loading="loading"
      >
        <el-table-column
          prop="review_result.student_file.original_name"
          label="论文标题"
          min-width="180"
          show-overflow-tooltip
        ></el-table-column>
        <el-table-column
          prop="appeal_content"
          label="申诉内容"
          min-width="200"
          show-overflow-tooltip
        ></el-table-column>
        <el-table-column
          prop="status"
          label="申诉状态"
          width="100"
        >
          <template slot-scope="scope">
            <el-tag :type="getStatusType(scope.row.status)">
              {{ getStatusText(scope.row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column
          prop="result"
          label="申诉结果"
          width="100"
        >
          <template slot-scope="scope">
            <el-tag :type="getResultType(scope.row.result)">
              {{ getResultText(scope.row.result) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column
          prop="arbitration_opinion"
          label="仲裁意见"
          min-width="200"
          show-overflow-tooltip
        >
          <template slot-scope="scope">
            {{ scope.row.arbitration_opinion || '-' }}
          </template>
        </el-table-column>
        <el-table-column
          prop="created_at"
          label="提交时间"
          width="160"
        >
          <template slot-scope="scope">
            {{ formatDate(scope.row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column
          prop="processed_at"
          label="处理时间"
          width="160"
        >
          <template slot-scope="scope">
            {{ scope.row.processed_at ? formatDate(scope.row.processed_at) : '-' }}
          </template>
        </el-table-column>
        <el-table-column
          label="操作"
          width="120"
          fixed="right"
        >
          <template slot-scope="scope">
            <el-button
              type="text"
              size="small"
              @click="handleAppeal(scope.row)"
              :disabled="scope.row.status === 'completed'"
            >
              处理申诉
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
          :current-page="currentPage"
          :page-sizes="[10, 20, 30, 50]"
          :page-size="pageSize"
          layout="total, sizes, prev, pager, next, jumper"
          :total="total"
        >
        </el-pagination>
      </div>
    </el-card>

    <el-dialog
      title="处理申诉"
      :visible.sync="dialogVisible"
      width="50%"
    >
      <div v-if="currentAppeal">
        <div class="appeal-info">
          <p><strong>论文标题：</strong>{{ getFileName(currentAppeal) }}</p>
          <p><strong>申诉内容：</strong>{{ currentAppeal.appeal_content }}</p>
          <p><strong>提交时间：</strong>{{ formatDate(currentAppeal.created_at) }}</p>
        </div>
        
        <el-form :model="appealForm" label-width="100px" class="appeal-form">
          <el-form-item label="申诉结果">
            <el-select v-model="appealForm.result" placeholder="请选择申诉结果">
              <el-option label="通过" value="approved"></el-option>
              <el-option label="驳回" value="rejected"></el-option>
            </el-select>
          </el-form-item>
          <el-form-item label="仲裁意见">
            <el-input
              type="textarea"
              v-model="appealForm.arbitration_opinion"
              :rows="4"
              placeholder="请输入仲裁意见"
            ></el-input>
          </el-form-item>
        </el-form>
      </div>
      <span slot="footer" class="dialog-footer">
        <el-button @click="dialogVisible = false">取 消</el-button>
        <el-button type="primary" @click="submitAppeal" :loading="submitLoading">确 定</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import axios from 'axios'
export default {
  name: 'AppealsList',
  data() {
    return {
      searchForm: {
        title: '',
        status: '',
        result: ''
      },
      appealsList: [],
      currentPage: 1,
      pageSize: 10,
      total: 0,
      loading: false,
      dialogVisible: false,
      currentAppeal: null,
      appealForm: {
        result: '',
        arbitration_opinion: ''
      },
      submitLoading: false
    }
  },
  methods: {
    async fetchAppealsList() {
      this.loading = true
      try {
        const response = await axios.get('/api/appeals', {
          params: {
            page: this.currentPage,
            pageSize: this.pageSize,
            ...this.searchForm
          }
        })
        
        this.appealsList = response.data.items
        this.total = response.data.total
      } catch (error) {
        console.error('获取申诉列表失败', error)
        this.$message.error('获取申诉列表失败')
      } finally {
        this.loading = false
      }
    },

    getStatusText(status) {
      const statusMap = {
        pending: '待处理',
        processing: '处理中',
        completed: '已完成'
      }
      return statusMap[status] || status
    },

    getStatusType(status) {
      const typeMap = {
        pending: 'warning',
        processing: 'primary',
        completed: 'success'
      }
      return typeMap[status] || 'info'
    },

    getResultText(result) {
      const resultMap = {
        pending: '待定',
        approved: '已通过',
        rejected: '已驳回'
      }
      return resultMap[result] || result
    },

    getResultType(result) {
      const typeMap = {
        pending: 'info',
        approved: 'success',
        rejected: 'danger'
      }
      return typeMap[result] || 'info'
    },

    formatDate(date) {
      if (!date) return ''
      return new Date(date).toLocaleString('zh-CN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      })
    },

    handleSearch() {
      this.currentPage = 1
      this.fetchAppealsList()
    },

    resetSearch() {
      this.searchForm = {
        title: '',
        status: '',
        result: ''
      }
      this.handleSearch()
    },

    handleSizeChange(val) {
      this.pageSize = val
      this.fetchAppealsList()
    },

    handleCurrentChange(val) {
      this.currentPage = val
      this.fetchAppealsList()
    },

    handleAppeal(row) {
      this.currentAppeal = row
      this.appealForm = {
        result: '',
        arbitration_opinion: ''
      }
      this.dialogVisible = true
    },

    async submitAppeal() {
      if (!this.appealForm.result) {
        this.$message.warning('请选择申诉结果')
        return
      }
      if (!this.appealForm.arbitration_opinion) {
        this.$message.warning('请输入仲裁意见')
        return
      }

      this.submitLoading = true
      try {
        await axios.put(`/api/appeals/${this.currentAppeal.id}`, this.appealForm)
        this.$message.success('处理成功')
        this.dialogVisible = false
        this.fetchAppealsList()
      } catch (error) {
        console.error('处理申诉失败', error)
        this.$message.error('处理申诉失败')
      } finally {
        this.submitLoading = false
      }
    },

    getFileName(appeal) {
      if (appeal && 
          appeal.review_result && 
          appeal.review_result.student_file) {
        return appeal.review_result.student_file.original_name
      }
      return '-'
    }
  },
  created() {
    this.fetchAppealsList()
  }
}
</script>

<style scoped>
.appeals-list {
  padding: 20px;
}

.search-form {
  margin-bottom: 20px;
}

.pagination-container {
  margin-top: 20px;
  text-align: right;
}

.appeal-info {
  background-color: #f5f7fa;
  padding: 15px;
  border-radius: 4px;
  margin-bottom: 20px;
}

.appeal-info p {
  margin: 8px 0;
}

.appeal-form {
  margin-top: 20px;
}
</style>
