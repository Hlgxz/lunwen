<template>
  <el-container class="thesis-management">
    <!-- 搜索区域 -->
    <el-card class="search-card">
      <el-form :inline="true" :model="searchForm" @submit.native.prevent="handleSearch">
        <el-form-item label="论文标题">
          <el-input v-model="searchForm.title" placeholder="请输入论文标题"></el-input>
        </el-form-item>
        <el-form-item label="自动评审状态">
          <el-select v-model="searchForm.auto_review_status" placeholder="请选择">
            <el-option label="全部" value=""></el-option>
            <el-option label="待检测" value="pending"></el-option>
            <el-option label="检测通过" value="success"></el-option>
            <el-option label="检测不通过" value="failed"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="人工审核状态">
          <el-select v-model="searchForm.manual_review_status" placeholder="请选择">
            <el-option label="全部" value=""></el-option>
            <el-option label="待审核" value="pending"></el-option>
            <el-option label="已通过" value="approved"></el-option>
            <el-option label="已拒绝" value="rejected"></el-option>
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="resetSearch">重置</el-button>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 表格区域 -->
    <el-card class="table-card">
      <el-table :data="reviewList" style="width: 100%">
        <el-table-column prop="file_name" label="论文标题"></el-table-column>
        <el-table-column label="自动评审状态">
          <template slot-scope="scope">
            {{ getAutoReviewStatusText(scope.row.auto_review_status) }}
          </template>
        </el-table-column>
        <el-table-column label="人工审核状态">
          <template slot-scope="scope">
            {{ getManualReviewStatusText(scope.row.manual_review_status) }}
          </template>
        </el-table-column>
        <el-table-column label="提交时间">
          <template slot-scope="scope">
            {{ formatDate(scope.row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200">
          <template slot-scope="scope">
            <el-button 
              type="primary" 
              size="small" 
              @click="viewReview(scope.row)"
            >查看</el-button>
            <el-button 
              v-if="scope.row.manual_review_status === 'pending'"
              type="warning"
              size="small"
              @click="showAppealModal(scope.row)"
            >发起申诉</el-button>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-container">
        <el-pagination
          background
          layout="prev, pager, next"
          :current-page.sync="currentPage"
          :page-size="pageSize"
          :total="total"
          @current-change="handlePageChange"
        >
        </el-pagination>
      </div>
    </el-card>

    <!-- 申诉对话框 -->
    <el-dialog
      title="发起申诉"
      :visible.sync="showModal"
      width="500px"
      :close-on-click-modal="false"
    >
      <el-form>
        <el-form-item label="申诉内容">
          <el-input
            type="textarea"
            v-model="appealContent"
            :rows="4"
            placeholder="请输入申诉内容"
          ></el-input>
        </el-form-item>
      </el-form>
      <span slot="footer" class="dialog-footer">
        <el-button @click="closeModal">取 消</el-button>
        <el-button 
          type="primary" 
          @click="submitAppeal"
          :disabled="!appealContent.trim()"
        >确 定</el-button>
      </span>
    </el-dialog>
  </el-container>
</template>

<script>
import axios from 'axios'
import { Message } from 'element-ui'

export default {
  name: 'ThesisManagement',
  data() {
    return {
      searchForm: {
        title: '',
        auto_review_status: '',
        manual_review_status: ''
      },
      reviewList: [],
      currentPage: 1,
      pageSize: 10,
      total: 0,
      showModal: false,
      appealContent: '',
      currentReview: null
    }
  },
  methods: {
    handleSearch() {
      this.currentPage = 1
      this.fetchThesisList()
    },
    resetSearch() {
      this.searchForm = {
        title: '',
        auto_review_status: '',
        manual_review_status: ''
      }
      this.handleSearch()
    },
    async fetchThesisList() {
      try {
        const response = await axios.get('/api/review-list', {
          params: {
            page: this.currentPage,
            pageSize: this.pageSize,
            ...this.searchForm
          }
        })
        this.reviewList = response.data.items
        this.total = response.data.total
      } catch (error) {
        Message.error('获取评审结果记录列表失败')
      }
    },
    handlePageChange() {
      this.fetchThesisList()
    },
    getAutoReviewStatusText(status) {
      const statusMap = {
        pending: '待检测',
        success: '检测通过',
        failed: '检测不通过'
      }
      return statusMap[status] || '未知'
    },
    getManualReviewStatusText(status) {
      const statusMap = {
        pending: '待审核',
        approved: '已通过',
        rejected: '已拒绝'
      }
      return statusMap[status] || '未知'
    },
    formatDate(dateString) {
      if (!dateString) return ''
      const date = new Date(dateString)
      return date.toLocaleDateString('zh-CN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    viewReview(review) {
      this.$router.push(`/review/${review.id}`)
    },
    showAppealModal(review) {
      this.currentReview = review
      this.showModal = true
      this.appealContent = ''
    },
    closeModal() {
      this.showModal = false
      this.appealContent = ''
      this.currentReview = null
    },
    async submitAppeal() {
      if (!this.appealContent.trim()) return

      try {
        const response = await axios.post(`/api/reviews/${this.currentReview.id}/appeal`, {
          appeal_content: this.appealContent
        })
        
        if (response.data.status === 'success') {
          Message.success(response.data.message)
          this.closeModal()
          this.fetchThesisList()
        } else {
          Message.error(response.data.message)
        }
      } catch (error) {
        Message.error(error.response?.data?.message || '申诉提交失败')
      }
    }
  },
  created() {
    this.fetchThesisList()
  }
}
</script>

<style scoped>
.thesis-management {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.search-card, .table-card {
  width: 100%;
}

.pagination-container {
  margin-top: 20px;
  text-align: center;
}

/* Element UI 表格内按钮间距 */
.el-button + .el-button {
  margin-left: 10px;
}
</style>
